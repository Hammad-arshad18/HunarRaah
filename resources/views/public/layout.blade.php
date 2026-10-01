<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('shared.theme')
    <title>@yield('title', config('platform.organization'))</title>
    <meta name="description" content="@yield('description', 'Focused courses and a clear learning journey.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', config('platform.organization'))">
    <meta property="og:description" content="@yield('description', 'Focused courses and a clear learning journey.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @stack('meta')
    @stack('scripts')
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('studio.css') }}?v={{ filemtime(public_path('studio.css')) }}">
</head>
<body>
    <a class="skip" href="#main">Skip to content</a>
    @unless(app()->isProduction())
        <div class="dev-banner">Development preview · Sample teaching content · Payments are not live</div>
    @endunless
    <header class="site-header wrap">
        <a class="wordmark" href="/"><img src="/favicon.svg" alt="" class="studio-mark">{{ config('platform.organization') }}</a>
        <nav aria-label="Main">
            <a href="/courses">Explore courses</a>
            @auth
                <a href="/dashboard">My learning</a>
            @else
                <a href="/login">Sign in</a>
                <a class="button small" href="/register">Start learning</a>
            @endauth
        </nav>
        <div class="public-theme-picker" data-theme-picker hidden>
            <button type="button" class="public-theme-trigger" data-theme-trigger aria-label="Choose colour theme" aria-haspopup="menu" aria-expanded="false" aria-controls="public-theme-menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 4v16a8 8 0 0 0 0-16Z" fill="currentColor" stroke="none"/></svg>
                <span>Theme</span>
                <svg class="theme-chevron" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m4 6 4 4 4-4"/></svg>
            </button>
            <div id="public-theme-menu" class="public-theme-options" data-theme-menu role="menu" aria-label="Colour theme" hidden>
                <button type="button" data-theme-option="light" role="menuitemradio" aria-checked="false" tabindex="-1">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/></svg>
                    Light
                </button>
                <button type="button" data-theme-option="dark" role="menuitemradio" aria-checked="false" tabindex="-1">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M20.8 13.2A9 9 0 0 1 10.8 3.2a9 9 0 1 0 10 10Z"/></svg>
                    Dark
                </button>
                <button type="button" data-theme-option="system" role="menuitemradio" aria-checked="false" tabindex="-1" title="Follow your device’s appearance">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>
                    System
                </button>
            </div>
        </div>
    </header>
    <main id="main">
        @if(session('operation_error'))
            <p class="wrap operation-error" role="alert">{{ session('operation_error') }}</p>
        @endif
        @yield('content')
    </main>
    <footer class="wrap site-footer">
        <div><strong>{{ config('platform.organization') }}</strong><p>A clear path from curiosity to practice.</p></div>
        <nav aria-label="Footer"><a href="/support">Support</a><a href="/terms">Terms</a><a href="/privacy">Privacy</a><a href="/refund-policy">Refund policy</a></nav>
    </footer>
</body>
</html>
