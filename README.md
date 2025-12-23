# Qnox Flutterwave (V4) PHP SDK

Packagist library that will wrap the Flutterwave v4 APIs for PHP and Laravel projects. The codebase is in early scaffolding; the public API may change until a stable release lands.

## Requirements
- PHP 8.1+
- Laravel 10 or 11 (optional; for auto-discovery)
- `guzzlehttp/guzzle` ^7 (installed via composer)

## Installation
```bash
composer require qnox/qnox_flutterwave
```

## Laravel integration
- The service provider is auto-discovered: `Qnox\QnoxFlutterwave\FlutterwaveServiceProvider`.
- A facade alias is registered for convenience: `Flutterwave` → `Qnox\QnoxFlutterwave\Facades\Flutterwave`.

## Configuration
Add your Flutterwave credentials to your environment (keys from the Flutterwave dashboard):
```
FLW_PUBLIC_KEY=your-public-key
FLW_SECRET_KEY=your-secret-key
FLW_ENCRYPTION_KEY=your-encryption-key
FLW_ENV=staging   # or production
```
Configuration wiring is still evolving; final config names and defaults will be documented alongside the first tagged release.

## Usage (preview)
Example of the intended facade-based experience; method names are subject to change while endpoints are implemented:
```php
use Qnox\QnoxFlutterwave\Facades\Flutterwave;

$payment = Flutterwave::payments()->initialize([
    'amount' => 100,
    'currency' => 'NGN',
    'tx_ref' => 'txn-123',
    'customer' => ['email' => 'customer@example.com'],
    'redirect_url' => 'https://example.com/redirect',
]);
```
Non-Laravel projects will be able to instantiate a client class directly once the core SDK is published.

## Roadmap
- Coverage of core v4 endpoints: payments, transfers, virtual accounts, settlements.
- Request/response helpers with typed DTOs.
- Laravel-friendly configuration and middleware hooks for webhook verification.

## Contributing
Issues and pull requests are welcome. Please describe the endpoint or feature you are targeting and any context from the Flutterwave docs to speed up review.

## License
MIT
