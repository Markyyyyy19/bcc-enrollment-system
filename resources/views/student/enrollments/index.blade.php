@extends('layouts.app')
@section('title', 'My applications')
@section('content')
    @php($student = auth()->user())
    <section class="page-heading-row">
        <div><h1 class="page-title">My applications</h1><p class="page-lede">Your enrollment, from submission to approval.</p></div>
        <a class="button button-primary" href="{{ route('student.enrollments.create') }}"><x-portal-icon name="plus" />New application</a>
    </section>
    <section class="panel list-panel student-applications-panel" aria-label="Enrollment applications">
        @if ($enrollments->isNotEmpty())
            <div class="table-wrap">
                <table class="responsive-table applications-table student-applications">
                    <thead><tr><th scope="col">Student ID</th><th scope="col">Full Name</th><th scope="col">Course</th><th scope="col">School Year</th><th scope="col">Semester</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
                    <tbody>
                        @foreach ($enrollments as $enrollment)
                            <tr>
                                <td data-label="Student ID"><span class="student-number">{{ $student->student_number ?: '—' }}</span></td>
                                <td data-label="Full Name"><strong>{{ trim($student->first_name.' '.$student->last_name) }}</strong></td>
                                <td data-label="Course"><strong>{{ $enrollment->program?->code ?? '—' }}</strong>@if ($enrollment->major?->name)<small>{{ $enrollment->major->name }}</small>@endif</td>
                                <td data-label="School Year">{{ $enrollment->school_year }}</td>
                                <td data-label="Semester">{{ $enrollment->semester }}</td>
                                <td data-label="Status"><span class="status-badge status-{{ $enrollment->status }}">{{ str_replace('_', ' ', $enrollment->status) }}</span>@if ($enrollment->registrar_note)<small class="registrar-note">{{ $enrollment->registrar_note }}</small>@endif</td>
                                <td class="table-action-cell" data-label="Action">
                                    @if ($enrollment->status === 'approved')
                                        <a class="button button-quiet button-small" href="{{ route('student.grades') }}">View grades <x-portal-icon name="arrow" /></a>
                                    @elseif (in_array($enrollment->status, ['draft', 'submitted'], true))
                                        <form method="post" action="{{ route('student.enrollments.withdraw', $enrollment) }}">@csrf @method('PATCH')<button class="button button-quiet button-small" type="submit">Withdraw</button></form>
                                    @else
                                        <span class="muted" aria-label="No action available">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($enrollments->hasPages())<div class="pagination-row">{{ $enrollments->links() }}</div>@endif
        @else
            <div class="empty-state"><span class="empty-mark"><x-portal-icon name="applications" /></span><h2>No applications yet</h2><p>Start a new application to enroll for your next term.</p></div>
        @endif
    </section>
@endsection
