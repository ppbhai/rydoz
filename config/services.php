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

    'msgclub' => [
        'base_url' => env('MSGCLUB_BASE_URL', 'https://msg.msgclub.net/rest/services/sendSMS/sendGroupSms'),
        'auth_key' => env('MSGCLUB_AUTH_KEY'),
        'sender_id' => env('MSGCLUB_SENDER_ID', 'DEMOOS'),
        'route_id' => env('MSGCLUB_ROUTE_ID', '1'),
        'sms_content_type' => env('MSGCLUB_SMS_CONTENT_TYPE', 'english'),
        'entity_id' => env('MSGCLUB_ENTITY_ID', 'NoneedIfAddedInPanel'),
        'tmid' => env('MSGCLUB_TMID', '140200000022'),
        'template_id' => env('MSGCLUB_TEMPLATE_ID', 'NoneedIfAddedInPanel'),
        'concent_failover_id' => env('MSGCLUB_CONCENT_FAILOVER_ID', '30'),
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

];
