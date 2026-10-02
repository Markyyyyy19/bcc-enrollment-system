<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')->withErrors(['email' => 'This BCC account is inactive. Contact the registrar for help.']);
        }

        if ($user->role === 'registrar') {
            return redirect()->route('registrar.applications.index');
        }

        return redirect()->route('student.enrollments.index');
    }
}
