@extends('public.layout')
@section('title', 'A little help with your next step')
@push('meta')
<meta name="robots" content="noindex,nofollow">
@endpush
@section('content')
<section class="wrap section reading">
    <p class="eyebrow">{{ $status }} / Let’s find your next step</p>
    <h1>This request couldn’t be completed.</h1>
    <p class="lead">{{ $message }}</p>
    <div class="actions"><a class="button" href="/courses">Explore courses →</a>@auth<a href="/dashboard">My learning</a>@endauth<a href="/support">Contact support</a></div>
</section>
@endsection
