<?php

namespace App\Http;

use Laravel\Fortify\Contracts\PasswordConfirmedResponse;

class AdminPasswordConfirmedResponse implements PasswordConfirmedResponse
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json('', 201);
        }
        $destination = $request->session()->pull('url.intended', $request->user()?->role === 'admin' ? '/admin' : '/dashboard');

        return $request->header('X-Inertia') && str_starts_with((string) parse_url($destination, PHP_URL_PATH), '/admin')
            ? \Inertia\Inertia::location($destination)
            : redirect($destination);
    }
}
