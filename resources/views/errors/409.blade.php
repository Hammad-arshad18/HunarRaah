@extends('public.layout')
@section('title','Action unavailable')
@section('content')<section class="wrap section reading"><h1>This action is unavailable.</h1><p>{{ $exception->getMessage() ?: 'The current course or account state does not permit this action.' }}</p><div class="actions"><a class="button" href="/dashboard">My learning</a><a href="/support">Contact support</a></div></section>@endsection
