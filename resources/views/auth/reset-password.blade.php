@extends('layouts.auth')

@section('title', 'Set a new password')

@section('form')
    <h1 class="auth-heading" id="auth-title">Set a new password</h1>
    <form class="form-stack auth-form" method="post" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="auth-field">
            <label class="field-label" for="email">Email address</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required autofocus>
        </div>
        <div class="auth-field">
            <label class="field-label" for="password">New password</label>
            <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" aria-describedby="password-requirements" data-password-input required>
            @include('auth.password-requirements')
        </div>
        <div class="auth-field">
            <label class="field-label" for="password_confirmation">Confirm new password</label>
            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </div>
        <button class="button button-primary button-wide" type="submit">Reset password</button>
    </form>
    <p class="auth-switch"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
