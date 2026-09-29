<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if (app()->isProduction()) {
            $nonce = Vite::cspNonce();
            $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'nonce-{$nonce}' https://embed.cloudflarestream.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; frame-src https://iframe.videodelivery.net https://*.cloudflarestream.com; media-src https://*.videodelivery.net https://*.cloudflarestream.com; connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'; upgrade-insecure-requests");
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
        if ($request->is('dashboard', 'learn/*', 'admin/*', 'orders/*', 'certificates/*', 'settings/*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
