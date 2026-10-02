@extends('layouts.auth')

@section('title', 'Forgot password')

@section('form')
    <h1 class="auth-heading" id="auth-title">Forgot password?</h1>
    <p class="auth-copy">Enter your BCC account email and we’ll send a reset link if the account exists.</p>
    <form class="form-stack auth-form" method="post" action="{{ route('password.email') }}">
        @csrf
        <div class="auth-field">
            <label class="field-label" for="email">Email address</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </div>
        <button class="button button-primary button-wide" type="submit">Send reset link</button>
    </form>
    <p class="auth-switch"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
