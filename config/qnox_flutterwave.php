<?php

return [
    'client_id'  => env('FLW_CLIENT_ID'),
    'client_secret'  => env('FLW_CLIENT_SECRET'),
    'encryption_key' => env('FLW_ENCRYPTION_KEY'),
    'secret_hash' => env('FLW_SECRET_HASH'), // for webhook signature verification
    'base_url'    => env('FLW_BASE_URL', 'https://developersandbox-api.flutterwave.com'),
    'token_url'   => env('FLW_TOKEN_URL', 'https://idp.flutterwave.com/realms/flutterwave/protocol/openid-connect/token'),
];
