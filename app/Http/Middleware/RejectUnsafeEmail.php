<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RejectUnsafeEmail
{
    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->input('email');
        if (is_string($email) && preg_match('/[\x00-\x1F\x7F]/', $email)) {
            throw ValidationException::withMessages(['email' => 'Enter an email address without control characters.']);
        }

        return $next($request);
    }
}
