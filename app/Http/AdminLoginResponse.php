<?php

namespace App\Http;

use Laravel\Fortify\Contracts\LoginResponse;

class AdminLoginResponse implements \Laravel\Fortify\Contracts\TwoFactorLoginResponse, LoginResponse
{
    public function toResponse($request)
    {
        // Successful sign-in already confirmed the password; do not ask for it again immediately.
        $request->session()->put('auth.password_confirmed_at', time());
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $destination = $request->session()->pull('url.intended', $request->user()?->role === 'admin' ? '/admin' : '/dashboard');

        return $request->header('X-Inertia') && str_starts_with((string) parse_url($destination, PHP_URL_PATH), '/admin')
            ? \Inertia\Inertia::location($destination)
            : redirect($destination);
    }
}
