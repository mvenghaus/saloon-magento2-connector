<?php

declare(strict_types=1);

namespace Mvenghaus\SaloonMagento2Connector;

use Closure;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
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
            $this->getAuthConnector()->updateAccessToken();
        } else {
            $authenticator = AccessTokenAuthenticator::unserialize($this->configuration->authenticator);

            $this->authenticate(new TokenAuthenticator($authenticator->getAccessToken()));

            if ($authenticator->hasExpired()) {
                $this->getAuthConnector()->updateAccessToken();
            }
        }
    }

    /**
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function send(Request $request, ?MockClient $mockClient = null, ?callable $handleRetry = null): Response
    {
        if ($this->getMockClient() === null) {
            $this->auth();
        }

        return parent::send($request, $mockClient, $handleRetry);
    }
}
