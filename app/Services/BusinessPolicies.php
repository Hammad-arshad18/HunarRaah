<?php

namespace App\Services;

class BusinessPolicies
{
    public static function ready(): bool
    {
        return config('platform.policies_approved') && config('platform.terms_version') !== 'draft'
            && is_file(resource_path('content/terms.md')) && is_file(resource_path('content/privacy.md')) && is_file(resource_path('content/refund-policy.md'));
    }
}
