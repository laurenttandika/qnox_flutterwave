# Qnox Flutterwave (V4) PHP SDK

Packagist-friendly SDK that wraps the Flutterwave v4 APIs for PHP and Laravel projects. Class/file names are prefixed with `Qnox` to avoid collisions with other packages.

## Requirements
- PHP 8.2+
- Laravel 8–12 (optional; for auto-discovery)
- `guzzlehttp/guzzle` ^7.7

## Installation
```bash
composer require qnox/qnox_flutterwave:^1.0.0
```

## Laravel integration
- Auto-discovered provider: `Qnox\QnoxFlutterwave\QnoxFlutterwaveServiceProvider`.
- Facade alias: `QnoxFlutterwave` → `Qnox\QnoxFlutterwave\Facades\QnoxFlutterwave`.
- Publish config (optional): `php artisan vendor:publish --tag=qnox-flutterwave-config`.

## Configuration
Environment keys (defaults target the Flutterwave sandbox):
```
FLW_CLIENT_ID=your-client-id #from flutterwave
FLW_CLIENT_SECRET=your-client-secret #from flutterwave
FLW_ENCRYPTION_KEY=your-base64-aes-key   # used for card field encryption from flutterwave
FLW_SECRET_HASH=your-webhook-secret-hash # for signature checks
FLW_MODE=sandbox  # or live; URLs are handled internally
```

## Usage
```php
use Qnox\QnoxFlutterwave\Facades\QnoxFlutterwave;

// Get OAuth token (auto-cached)
$token = QnoxFlutterwave::getAccessToken();
$accessToken = $token['access_token'];

// Create or search a customer
$customer = QnoxFlutterwave::createCustomer($accessToken, 'user@example.com');

// Create a mobile-money payment method
$method = QnoxFlutterwave::createMobilePaymentMethod($accessToken, [
    'mobile_money' => ['network' => 'AIRTEL', 'country_code' => 255, 'phone_number' => '713380217'],
    'type' => 'mobile_money',
    'customer_id' => $customer['data']['id'],
]);

// Charge the customer
$charge = QnoxFlutterwave::createCharge($accessToken, [
    'currency' => 'TZS',
    'recurring' => false,
    'amount' => 2000,
    'reference' => 'ref-' . time(),
    'customer_id' => $customer['data']['id'],
    'payment_method_id' => $method['data']['id'],
    'redirect_url' => 'https://example.com/redirect',
]);
```

### Card encryption helper
```php
$nonce = QnoxFlutterwave::generateNonce(); // e.g. 12 chars
$encryptedNumber = QnoxFlutterwave::encryptGCMField($cardNumber, $nonce, env('FLW_ENCRYPTION_KEY'));
```

### Webhook verification
```php
$isValid = QnoxFlutterwave::verifyWebhookSignature(
    request()->header('flutterwave-signature'),
    request()->getContent()
);
abort_unless($isValid, 401);
```

## Roadmap
- Add higher-level DTOs and validation around request payloads.
- Expand coverage beyond customers/payment methods/charges (transfers, settlements, virtual accounts).
- More Laravel niceties: middleware for webhook verification and test fakes.

## Contributing
Issues and PRs are welcome. Please describe the endpoint/flow you’re targeting and link the relevant Flutterwave docs.

## License
MIT
 
