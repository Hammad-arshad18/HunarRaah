<?php

namespace App\Http;

use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;

class GenericPasswordResetResponse implements FailedPasswordResetLinkRequestResponse
{
    public function __construct(public string $status) {}

    public function toResponse($request)
    {
        return $request->wantsJson() ? response()->json(['message' => __('passwords.sent')]) : back()->with('status', __('passwords.sent'));
    }
}
