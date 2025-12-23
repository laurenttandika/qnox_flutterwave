<?php

return [
    'client_id'  => env('FLW_CLIENT_ID'),
    'client_secret'  => env('FLW_CLIENT_SECRET'),
    'encryption_key' => env('FLW_ENCRYPTION_KEY'),
    'secret_hash' => env('FLW_SECRET_HASH'), // for webhook signature verification
    // mode: sandbox (default) or live
    'mode'        => env('FLW_MODE', 'sandbox'),
];
