<?php

declare(strict_types=1);

namespace Mvenghaus\SaloonMagento2Connector;

use Closure;
use DateInterval;
use DateTimeImmutable;
use Saloon\Contracts\OAuthAuthenticator;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Auth\AccessTokenAuthenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\OAuth2\ClientCredentialsGrant;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Mvenghaus\SaloonMagento2Connector\Requests\GetAccessTokenRequest;

class Connector extends \Saloon\Http\Connector
{
    use AlwaysThrowOnErrors;
    use ClientCredentialsGrant;

    public function __construct(
        public Configuration $configuration,
    ) {
        if ($this->configuration->debugCallback instanceof Closure) {
            $this->debugRequest($this->configuration->debugCallback);
        }

        if (empty($this->configuration->authenticator)) {
            $this->updateAccessToken();
            return;
        }

        $authenticator = AccessTokenAuthenticator::unserialize($this->configuration->authenticator);

        $this->authenticate(new TokenAuthenticator($authenticator->getAccessToken()));

        if ($authenticator->hasExpired()) {
            $this->updateAccessToken();
        }
    }

    public function resolveBaseUrl(): string
    {
        return $this->configuration->endpoint;
    }

    public function hasRequestFailed(Response $response): ?bool
    {
        return $response->status() !== 200;
    }

    private function updateAccessToken(): void
    {
        $authenticator = $this->getAccessToken();

        $this->authenticate(new TokenAuthenticator($authenticator->getAccessToken()));

        if ($this->configuration->authenticatorUpdateCallback instanceof Closure) {
            ($this->configuration->authenticatorUpdateCallback)($authenticator->serialize());
        }
    }

    protected function defaultOauthConfig(): OAuthConfig
    {
        return OAuthConfig::make()
            ->setClientId($this->configuration->username)
            ->setClientSecret($this->configuration->password)
            ->setTokenEndpoint('integration/admin/token');
    }

    protected function resolveAccessTokenRequest(
        OAuthConfig $oauthConfig,
        array $scopes = [],
        string $scopeSeparator = ' '
    ): Request {
        return new GetAccessTokenRequest($oauthConfig);
    }

    protected function createOAuthAuthenticatorFromResponse(Response $response): OAuthAuthenticator
    {
        $accessToken = trim($response->body(), '"');
        $expiresAt = (new DateTimeImmutable)->add(
            DateInterval::createFromDateString($this->configuration->tokenLifetime . ' seconds')
        );

        return $this->createOAuthAuthenticator($accessToken, $expiresAt);
    }
}
