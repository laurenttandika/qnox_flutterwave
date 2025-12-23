<?php

namespace Qnox\QnoxFlutterwave;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class QnoxFlutterwaveServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/qnox_flutterwave.php', 'qnox_flutterwave');

        $this->app->singleton(QnoxFlutterwave::class, function ($app) {
            $config = $app['config']->get('qnox_flutterwave', []);
            $mode = strtolower($config['mode'] ?? 'sandbox');

            $baseUrl = $mode === 'live'
                ? QnoxFlutterwave::DEFAULT_LIVE_BASE_URL
                : QnoxFlutterwave::DEFAULT_SANDBOX_BASE_URL;

            $baseUrl = rtrim($baseUrl, '/');

            $http = new Client([
                'base_uri' => $baseUrl . '/',
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            return new QnoxFlutterwave(
                httpClient: $http,
                baseUrl: $baseUrl,
                clientId: $config['client_id'] ?? '',
                clientSecret: $config['client_secret'] ?? '',
                encryptionKey: $config['encryption_key'] ?? '',
                secretHash: $config['secret_hash'] ?? ''
            );
        });

        $this->app->alias(QnoxFlutterwave::class, 'qnox_flutterwave');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/qnox_flutterwave.php' => config_path('qnox_flutterwave.php'),
        ], 'qnox-flutterwave-config');
    }
}
