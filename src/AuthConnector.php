<?php

declare(strict_types=1);

namespace Mvenghaus\SaloonMagento2Connector;

use Closure;
use DateInterval;
use DateTimeImmutable;
use Mvenghaus\SaloonMagento2Connector\Requests\GetAccessTokenRequest;
use Saloon\Contracts\OAuthAuthenticator;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\OAuth2\ClientCredentialsGrant;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class AuthConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;
    use ClientCredentialsGrant;

    public function __construct(
        public Configuration $configuration,
    ) {
        if ($this->configuration?->debugCallback instanceof Closure) {
            $this->debugRequest($this->configuration->debugCallback);
        }
    }

    public function hasRequestFailed(Response $response): ?bool
    {
        return $response->status() !== 200;
    }

    public function updateAccessToken(): OAuthAuthenticator
    {
        $authenticator = $this->getAccessToken();

        if ($this->configuration->authenticatorUpdateCallback instanceof Closure) {
            ($this->configuration->authenticatorUpdateCallback)($authenticator->serialize());
        }

        return $authenticator;
    }

    public function resolveBaseUrl(): string
    {
        return $this->configuration->endpoint;
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
            DateInterval::createFromDateString($this->configuration->tokenLifetime.' seconds')
        );

        return $this->createOAuthAuthenticator($accessToken, $expiresAt);
    }
}
