<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'fayko' => [
        'public_key' => env('FAYKO_PUBLIC_KEY'),
        'secret_key' => env('FAYKO_SECRET_KEY'),
        'webhook_key' => env('FAYKO_WEBHOOK_KEY'),
        'mode' => env('FAYKO_MODE', 'LIVE'), // TEST ou LIVE
    ],

    'intech_sms' => [
        'app_key' => env('INTECH_SMS_APP_KEY', '68863D3BABAEA68863D3BABAEB'),
        'sender' => env('INTECH_SMS_SENDER', 'KSSV.SN'),
        'endpoint' => env('INTECH_SMS_ENDPOINT', 'https://gateway.intechsms.sn/api/send-sms'),
    ],

];
