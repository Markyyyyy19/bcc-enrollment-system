@extends('layouts.auth')

@section('title', 'Sign in')

@section('form')
    <h1 class="auth-heading" id="auth-title">Sign in</h1>
    <form class="form-stack auth-form" method="post" action="{{ route('login.store') }}">
        @csrf
        <div class="auth-field">
            <label class="field-label" for="email">Email address</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
        </div>
        <div class="auth-field">
            <div class="label-row">
                <label class="field-label" for="password">Password</label>
                <a class="auth-help-link" href="{{ route('password.request') }}">Forgot password?</a>
            </div>
            <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
        </div>
        <button class="button button-primary button-wide" type="submit">Sign in</button>
    </form>
    <p class="auth-switch">New student? <a href="{{ route('register') }}">Create an account</a></p>
@endsection
