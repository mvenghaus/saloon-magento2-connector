<?php

declare(strict_types=1);

namespace Mvenghaus\SaloonMagento2Connector\Requests;

use Saloon\Enums\Method;
use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Request;

final class GetAccessTokenRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        protected OAuthConfig $oauthConfig
    ) {
    }

    public function resolveEndpoint(): string
    {
        return $this->oauthConfig->getTokenEndpoint();
    }

    public function defaultQuery(): array
    {
        return [
            'username' => $this->oauthConfig->getClientId(),
            'password' => $this->oauthConfig->getClientSecret(),
        ];
    }
}
