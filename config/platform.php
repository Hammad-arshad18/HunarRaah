<?php

return [
    'organization' => env('ORGANIZATION_NAME', 'Teaching Studio'),
    'issuer' => env('CERTIFICATE_ISSUER', 'Teaching Studio'),
    'signatory' => env('CERTIFICATE_SIGNATORY'),
    'support_email' => env('SUPPORT_EMAIL'),
    'currency' => env('SITE_CURRENCY', 'AED'),
    'timezone' => env('BUSINESS_TIMEZONE', 'Asia/Dubai'),
    'merchant_country' => env('MERCHANT_COUNTRY'),
    'terms_version' => env('TERMS_VERSION', 'draft'),
    'policies_approved' => env('POLICIES_APPROVED', false),
    'tax_policy' => env('PRICE_TAX_POLICY'),
    'meeting_hosts' => array_filter(explode(',', (string) env('MEETING_ALLOWED_HOSTS', 'zoom.us,meet.google.com'))),
    'linkedin_url' => env('LINKEDIN_ADD_TO_PROFILE_URL'),
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'live_enabled' => env('STRIPE_LIVE_ENABLED', false),
    ],
    'stream' => [
        'account' => env('STREAM_ACCOUNT_ID'),
        'token' => env('STREAM_API_TOKEN'),
        'customer_code' => env('STREAM_CUSTOMER_CODE'),
    ],
];
