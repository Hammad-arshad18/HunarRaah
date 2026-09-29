<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SensitiveAuthThrottle
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('post') && $request->is('forgot-password', 'email/verification-notification', 'register')) {
            $email = strtolower(trim($request->user() ? $request->user()->email : $request->input('email', '')));
            $key = 'sensitive-auth:'.$request->path().':'.hash('sha256', $email.'|'.$request->ip());
            $ipKey = 'sensitive-auth-ip:'.$request->path().':'.$request->ip();
            abort_if(RateLimiter::tooManyAttempts($key, $request->is('register') ? 5 : 3) || RateLimiter::tooManyAttempts($ipKey, 30), 429, 'Please wait before trying again.');
            RateLimiter::hit($key, 900);
            RateLimiter::hit($ipKey, 900);
        }

        return $next($request);
    }
}
