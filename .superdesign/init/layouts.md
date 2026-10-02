# Layouts
Authenticated shell: resources/views/layouts/app.blade.php
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BCC Portal</title>
    <link rel="icon" href="{{ asset('images/bcc-logo-web.png') }}">
    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="workspace-body">
    @php
        $registrarNavigation = [
            ['label' => 'Applications', 'route' => 'registrar.applications.index', 'match' => 'registrar.applications.*', 'icon' => 'â–¤'],
            ['label' => 'Students', 'route' => 'registrar.students', 'match' => 'registrar.students', 'icon' => 'â™§'],
            ['label' => 'Programs', 'route' => 'registrar.programs', 'match' => 'registrar.programs*', 'icon' => 'â–¦'],
            ['label' => 'Subjects', 'route' => 'registrar.subjects', 'match' => 'registrar.subjects', 'icon' => 'â–§'],
            ['label' => 'Grades', 'route' => 'registrar.grades', 'match' => 'registrar.grades', 'icon' => 'â†—'],
            ['label' => 'Reports', 'route' => 'registrar.reports', 'match' => 'registrar.reports', 'icon' => 'â‡©'],
        ];
        $studentNavigation = [
            ['label' => 'New enrollment', 'route' => 'student.enrollments.create', 'match' => 'student.enrollments.create', 'icon' => 'ï¼‹'],
            ['label' => 'My applications', 'route' => 'student.enrollments.index', 'match' => 'student.enrollments.*', 'icon' => 'â–¤'],
            ['label' => 'My grades', 'route' => 'student.grades', 'match' => 'student.grades', 'icon' => 'â†—'],
        ];
        $navigation = auth()->user()?->role === 'registrar' ? $registrarNavigation : $studentNavigation;
    @endphp
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand-lockup" href="{{ route('dashboard') }}" aria-label="Go to dashboard">
                <img src="{{ asset('images/bcc-logo-web.png') }}" alt="" width="48" height="48">
            </a>
            <div class="sidebar-divider"></div>
            <p class="nav-caption">{{ auth()->user()?->role === 'registrar' ? 'College office' : 'Student portal' }}</p>
            <nav class="primary-nav" aria-label="Main navigation">
                @foreach ($navigation as $item)
                    @php($isActive = request()->routeIs($item['match']) && ! ($item['route'] === 'student.enrollments.index' && request()->routeIs('student.enrollments.create')))
                    <a href="{{ route($item['route']) }}" @class(['nav-link', 'is-active' => $isActive]) @if($isActive) aria-current="page" @endif>
                        <span class="nav-glyph" aria-hidden="true">{{ $item['icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>
        <main class="main-area">
            <header class="topbar">
                <div class="mobile-brand"><img src="{{ asset('images/bcc-logo-web.png') }}" alt="" width="36" height="36"><span><strong>BCC</strong></span></div>
                <div class="topbar-user">
                    <div class="user-profile">
                        <img class="avatar" src="{{ asset('images/user-avatar.svg') }}" alt="" width="36" height="36" aria-hidden="true">
                        <span class="user-meta"><strong>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</strong></span>
                    </div>
                    <form class="sign-out-form" method="post" action="{{ route('logout') }}">
                        @csrf
                        <button class="sign-out" type="submit">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            <span>Sign out</span>
                        </button>
                    </form>
                </div>
            </header>
            <div class="page-wrap">
                @if (session('success'))
                    <div class="notice notice-success" role="status"><span aria-hidden="true">âœ“</span>{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="notice notice-error" role="alert"><span aria-hidden="true">!</span><span>{{ $errors->first() }}</span></div>
                @endif
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
