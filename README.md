# Saloon - Magento 2 Connector

[Saloon](https://docs.saloon.dev/) - Magento 2 Connector with token handling, allowing you to easily start building your own requests. It is only working if 2FA is disabled.

## Installation

Install the package via composer:

```bash
composer require mvenghaus/saloon-magento2-connector
```

## Usage

### Basic Structure

```php

$configuration = new Configuration(...);
$connector = new Connector($configuration);

$response = $connector->send(new Your_Request());
```

### Configuration - Structure

```php
class Configuration
{
    public function __construct(
        public string $endpoint, // https://www.your-domain.com/rest/V1/
        public string $username,
        public string $password,
        public int $tokenLifetime = 0, // admin defined token lifetime in seconds 
        public ?string $authenticator = null, // saloon authenticator (serialized)
        public ?Closure $authenticatorUpdateCallback = null, // callback to save authenticator if changed
        public ?Closure $debugCallback = null // callback for debugging
    ) {
    }
}
```

### Configuration - Example

```php
$authenticator = load_from_your_cache();

$configuration = new Configuration(
    'https://www.your-domain.com/rest/V1/',
    'USERNAME',
    'PASSWORD',
    3600,
    $authenticator,
    function (string $authenticator) {
        save_to_your_cache($authenticator);
    },
    function (PendingRequest $pendingRequest, RequestInterface $psrRequest) {
        echo $pendingRequest->getUrl() . PHP_EOL;
    }
);
```