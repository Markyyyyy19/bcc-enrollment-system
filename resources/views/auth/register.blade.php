@extends('layouts.auth')

@section('title', 'Create student account')

@section('form')
    <h1 class="auth-heading" id="auth-title">Create account</h1>
    <form class="form-stack auth-form" method="post" action="{{ route('register.store') }}">
        @csrf
        <div class="auth-field">
            <label class="field-label" for="student_number">Student number</label>
            <input class="form-control" id="student_number" name="student_number" value="{{ old('student_number') }}" autocomplete="off" required>
        </div>
        <div class="field-pair">
            <div class="auth-field"><label class="field-label" for="first_name">First name</label><input class="form-control" id="first_name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required></div>
            <div class="auth-field"><label class="field-label" for="last_name">Last name</label><input class="form-control" id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required></div>
        </div>
        <div class="auth-field">
            <label class="field-label" for="email">Email address</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
        </div>
        <div class="auth-field">
            <label class="field-label" for="password">Password</label>
            <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" aria-describedby="password-requirements" data-password-input required>
            @include('auth.password-requirements')
        </div>
        <div class="auth-field">
            <label class="field-label" for="password_confirmation">Confirm password</label>
            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </div>
        <button class="button button-primary button-wide" type="submit">Create account</button>
    </form>
    <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
@endsection
