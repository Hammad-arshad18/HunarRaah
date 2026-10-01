<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSetupController extends Controller
{
    public function __invoke(Request $request): \Inertia\Response
    {
        abort_unless($request->user()->role === 'admin', 403);

        return Inertia::render('auth/admin-setup', [
            'twoFactorEnabled' => $request->user()->hasEnabledTwoFactorAuthentication(),
            'pendingSetup' => (bool) $request->user()->two_factor_secret && ! $request->user()->two_factor_confirmed_at,
        ]);
    }
}
