<?php

namespace App\Http;

class StudioLogoutResponse implements \Laravel\Fortify\Contracts\LogoutResponse, \Filament\Http\Responses\Auth\Contracts\LogoutResponse
{
    public function toResponse($request)
    {
        if ($request->header('X-Inertia')) {
            return \Inertia\Inertia::location(url('/'));
        }

        return $request->wantsJson() ? response()->noContent() : redirect('/');
    }
}
