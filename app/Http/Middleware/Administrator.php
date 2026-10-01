<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Administrator
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->role === 'admin', 403);
        if (! $request->user()->two_factor_confirmed_at || ! $request->user()->two_factor_secret) {
            return redirect('/admin/setup');
        }

        return $next($request);
    }
}
