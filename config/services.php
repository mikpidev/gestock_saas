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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'hacienda' => [
        'base_url' => env('HACIENDA_API_URL_TEST'),
        'user' => env('HACIENDA_USER'),
        'pass' => env('HACIENDA_PASS'),
    ],
    
    'firma' => [
        'url' => env('FIRMA_API_URL'),
    ],

    'oci' => [
        'tenancy_id' => env('OCI_TENANCY_ID'),
        'user_id' => env('OCI_USER_ID'),
        'fingerprint' => env('OCI_FINGERPRINT'),
        'region' => env('OCI_REGION'),
        'key_file' => env('OCI_KEY_FILE'),
        'bucket' => env('OCI_BUCKET'),
    ],
    

];
