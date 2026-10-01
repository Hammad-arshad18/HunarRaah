<link rel="stylesheet" href="{{ asset('theme.css') }}?v={{ filemtime(public_path('theme.css')) }}">
<script src="{{ asset('theme.js') }}?v={{ filemtime(public_path('theme.js')) }}" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}"></script>
