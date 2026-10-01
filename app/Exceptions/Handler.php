<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    public function render($request, \Throwable $e)
    {
        $response = parent::render($request, $e);
        if ((! $request->expectsJson() || $request->header('X-Inertia')) && in_array($response->getStatusCode(), [403, 404, 409, 419, 422, 429, 500, 503], true)) {
            $status = $response->getStatusCode();
            $message = match ($status) {
                403 => 'Your account does not have access to this action. Check your verification and enrollment, or contact support.',
                404 => 'This record could not be found.',
                419 => 'Your session expired. Refresh this page and try again.',
                429 => 'Please wait a moment before trying again.',
                500 => 'We could not complete this request. Please try again or contact support.',
                503 => 'This service is not configured or is temporarily unavailable. Your access has not been changed. Contact support if you need help.',
                default => $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getMessage() : 'This change could not be completed.',
            };
            if (! $request->header('X-Inertia')) {
                return response()->view('public.error', compact('status', 'message'), $status)->header('Cache-Control', 'no-store, private');
            }
            if (! $request->isMethod('GET')) {
                return back()->with('operation_error', $message);
            }

            return \Inertia\Inertia::render('studio/error', ['status' => $status, 'message' => $message])->toResponse($request)->setStatusCode($status);
        }

        return $response;
    }

    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];
}
