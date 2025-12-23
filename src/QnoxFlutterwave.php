<?php

namespace Qnox\QnoxFlutterwave;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class QnoxFlutterwave
{
    private const TOKEN_CACHE_KEY = 'qnox_flutterwave_access_token';
    private const DEFAULT_EXPIRES_IN = 600;
    private const DEFAULT_BASE_URL = 'https://developersandbox-api.flutterwave.com';
    private const DEFAULT_TOKEN_URL = 'https://idp.flutterwave.com/realms/flutterwave/protocol/openid-connect/token';

    public function __construct(
        private Client $httpClient,
        private string $baseUrl = self::DEFAULT_BASE_URL,
        private string $tokenUrl = self::DEFAULT_TOKEN_URL,
        private string $clientId = '',
        private string $clientSecret = '',
        private string $encryptionKey = '',
        private string $secretHash = ''
    ) {
    }

    public function getAccessToken(): array
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if (
            $cached
            && isset($cached['access_token'], $cached['expires_at'])
            && Carbon::parse($cached['expires_at'])->isFuture()
        ) {
            return [
                'source' => 'cache',
                'access_token' => $cached['access_token'],
                'expires_at' => $cached['expires_at'],
            ];
        }

        $response = $this->httpClient->post($this->tokenUrl, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials',
            ],
        ]);

        $payload = json_decode((string) $response->getBody(), true);

        $expiresIn = $payload['expires_in'] ?? self::DEFAULT_EXPIRES_IN;
        $expiresAt = Carbon::now()->addSeconds($expiresIn);

        Cache::put(self::TOKEN_CACHE_KEY, [
            'access_token' => $payload['access_token'] ?? null,
            'expires_at' => $expiresAt->toDateTimeString(),
        ], $expiresAt);

        return [
            'source' => 'api',
            'access_token' => $payload['access_token'] ?? null,
            'expires_at' => $expiresAt->toDateTimeString(),
            'raw' => $payload,
        ];
    }

    public function getCustomers(string $accessToken, int $page = 1): array
    {
        return $this->request('GET', 'customers', [
            'headers' => $this->headers($accessToken),
            'query' => ['page' => $page],
        ]);
    }

    public function createCustomer(string $accessToken, string $email, array $attributes = []): array
    {
        $payload = array_filter(array_merge($attributes, ['email' => $email]), static fn($value) => !is_null($value));

        return $this->request('POST', 'customers', [
            'headers' => $this->headers($accessToken),
            'json' => $payload,
        ]);
    }

    public function getCustomer(string $accessToken, string $customerId): array
    {
        return $this->request('GET', 'customers/' . $customerId, [
            'headers' => $this->headers($accessToken),
        ]);
    }

    public function updateCustomer(string $accessToken, string $customerId, array $attributes = []): array
    {
        $payload = array_filter($attributes, static fn($value) => !is_null($value));

        try {
            return $this->request('PUT', 'customers/' . $customerId, [
                'headers' => $this->headers($accessToken),
                'json' => $payload,
            ]);
        } catch (Exception $e) {
            Log::debug($e->getMessage());
            throw $e;
        }
    }

    public function searchCustomer(string $accessToken, string $email): array
    {
        return $this->request('POST', 'customers/search', [
            'headers' => $this->headers($accessToken),
            'query' => [
                'page' => 1,
                'size' => 10,
            ],
            'json' => [
                'email' => $email,
            ],
        ]);
    }

    public function getPaymentMethods(string $accessToken, int $page = 1, int $size = 10): array
    {
        return $this->request('GET', 'payment-methods', [
            'headers' => $this->headers($accessToken),
            'query' => [
                'page' => $page,
                'size' => $size,
            ],
        ]);
    }

    public function createCardPaymentMethod(string $accessToken, array $attributes): array
    {
        $payload = array_filter($attributes, static fn($value) => !is_null($value));

        return $this->request('POST', 'payment-methods', [
            'headers' => $this->headers($accessToken),
            'json' => $payload,
        ]);
    }

    public function createMobilePaymentMethod(string $accessToken, array $attributes): array
    {
        $payload = array_filter($attributes, static fn($value) => !is_null($value));

        return $this->request('POST', 'payment-methods', [
            'headers' => $this->headers($accessToken),
            'json' => $payload,
        ]);
    }

    public function getPaymentMethod(string $accessToken, string $methodId): array
    {
        return $this->request('GET', 'payment-methods/' . $methodId, [
            'headers' => $this->headers($accessToken),
        ]);
    }

    public function getMobileNetworks(string $accessToken, string $countryIso = 'TZ'): array
    {
        return $this->request('GET', 'mobile-networks', [
            'headers' => $this->headers($accessToken),
            'query' => ['country' => $countryIso],
        ]);
    }

    public function getTransactionFee(string $accessToken, array $attributes): array
    {
        return $this->request('GET', 'fees', [
            'headers' => $this->headers($accessToken),
            'query' => $attributes,
        ]);
    }

    public function getCharges(string $accessToken, array $attributes, int $page = 1): array
    {
        $payload = array_filter($attributes, static fn($value) => !is_null($value));

        $payload['page'] = $payload['page'] ?? $page;

        return $this->request('GET', 'charges', [
            'headers' => $this->headers($accessToken),
            'query' => $payload,
        ]);
    }

    public function getCharge(string $accessToken, string $chargeId): array
    {
        return $this->request('GET', 'charges/' . $chargeId, [
            'headers' => $this->headers($accessToken),
        ]);
    }

    public function createCharge(string $accessToken, array $attributes): array
    {
        $payload = array_filter($attributes, static fn($value) => !is_null($value));

        $headers = $this->headers($accessToken);
        if (isset($payload['reference'])) {
            $headers['X-Idempotency-Key'] = $payload['reference'];
        }
        if (isset($payload['scenario_key'])) {
            $headers['X-Scenario-Key'] = $payload['scenario_key'];
            unset($payload['scenario_key']);
        }

        return $this->request('POST', 'charges', [
            'headers' => $headers,
            'json' => $payload,
        ]);
    }

    public function verifyWebhookSignature(?string $signature, string $payload = ''): bool
    {
        if ($this->secretHash === '' || $signature === null) {
            return false;
        }

        if ($payload === '') {
            return hash_equals($this->secretHash, $signature);
        }

        $computed = hash_hmac('sha256', $payload, $this->secretHash);

        return hash_equals($computed, $signature) || hash_equals($this->secretHash, $signature);
    }

    public function generateNonce(int $length = 12): string
    {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        $result = "";

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $result;
    }

    public function encryptGCMField(string $plaintext, string $nonce, string $b64Key): string
    {
        if ($plaintext === '') {
            throw new InvalidArgumentException("Must provide valid plaintext");
        }
        if ($b64Key === '') {
            throw new InvalidArgumentException("Must provide valid b64EncodedAESKey");
        }
        if ($nonce === '') {
            throw new InvalidArgumentException("Must provide valid nonce");
        }

        $key = base64_decode($b64Key);

        $tag = '';
        $cipher = 'aes-256-gcm';

        $encrypted = openssl_encrypt(
            $plaintext,
            $cipher,
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            "",
            16
        );

        $combined = $encrypted . $tag;

        return base64_encode($combined);
    }

    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }

    public function getEncryptionKey(): string
    {
        return $this->encryptionKey;
    }

    /**
     * Basic request helper; subject to change as endpoints are added.
     *
     * @throws GuzzleException
     */
    private function request(string $method, string $uri, array $options = []): array
    {
        $response = $this->httpClient->request(
            $method,
            ltrim($uri, '/'),
            $options
        );

        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function headers(?string $accessToken = null, array $extra = []): array
    {
        $headers = [
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($accessToken) {
            $headers['Authorization'] = 'Bearer ' . $accessToken;
        }

        return array_merge($headers, $extra);
    }
}
