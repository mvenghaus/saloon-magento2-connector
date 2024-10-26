<?php

declare(strict_types=1);

namespace Mvenghaus\SaloonMagento2Connector;

use Closure;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Exceptions\Request\Statuses\UnauthorizedException;
use Saloon\Http\Auth\AccessTokenAuthenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\Traits\OAuth2\ClientCredentialsGrant;
use Saloon\Traits\Plugins\AcceptsJson;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

class ApiConnector extends Connector
{
    use AcceptsJson;
    use AlwaysThrowOnErrors;
    use ClientCredentialsGrant;

    public function __construct(
        public Configuration $configuration,
    ) {
        if ($this->configuration->debugCallback instanceof Closure) {
            $this->debugRequest($this->configuration->debugCallback);
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

    public function getAuthConnector(): AuthConnector
    {
        return new AuthConnector($this->configuration);
    }

    public function auth(): void
    {
        if (empty($this->configuration->authenticator)) {
            $authenticator = $this->getAuthConnector()->updateAccessToken();
        } else {
            $authenticator = AccessTokenAuthenticator::unserialize($this->configuration->authenticator);
            if ($authenticator->hasExpired()) {
                $this->getAuthConnector()->updateAccessToken();
            }
        }

        $this->authenticate(new TokenAuthenticator($authenticator->getAccessToken()));
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     * @throws UnauthorizedException
     */
    public function send(Request $request, ?MockClient $mockClient = null, ?callable $handleRetry = null): Response
    {
        if ($this->getMockClient() !== null) {
            return parent::send($request, $mockClient, $handleRetry);
        }

        $this->auth();

        try {
            return parent::send($request, $mockClient, $handleRetry);
        } catch (UnauthorizedException) {
            $this->configuration->authenticator = null;
            $this->auth();
        }

        return parent::send($request, $mockClient, $handleRetry);
    }
}
