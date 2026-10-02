@extends('layouts.app')

@section('title', 'Student directory')
@section('eyebrow', 'Registrar / Students')

@section('content')
    <section class="page-heading-row">
        <div>
            <p class="section-kicker">Campus community</p>
            <h1 class="page-title">Student directory<span class="title-period">.</span></h1>
            <p class="page-lede">Maintain student profiles and account access.</p>
        </div>
    </section>
    <section class="panel list-panel">
        <div class="table-wrap">
            <table class="responsive-table student-directory-table">
                <thead><tr><th>Student</th><th>Student number</th><th>Latest program</th><th>Email</th><th>Account</th><th>Joined</th><th></th></tr></thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td><strong>{{ $student->first_name }} {{ $student->last_name }}</strong></td>
                            <td>{{ $student->student_number ?? '—' }}</td>
                            <td>{{ $student->latest_program_code ?? '—' }}@if ($student->latest_enrollment_status)<small><span class="status-badge status-{{ $student->latest_enrollment_status }}">{{ str_replace('_', ' ', $student->latest_enrollment_status) }}</span></small>@endif</td>
                            <td>{{ $student->email }}</td>
                            <td><span class="status-badge {{ $student->is_active ? 'status-approved' : 'status-rejected' }}">{{ $student->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $student->created_at?->format('M j, Y') }}</td>
                            <td>
                                <details class="review-details">
                                    <summary>Manage</summary>
                                    <div class="student-actions">
                                        <form class="student-edit-form" method="post" action="{{ route('registrar.students.update', $student) }}">
                                            @csrf
                                            @method('PATCH')
                                            <label class="field-label" for="student-number-{{ $student->id }}">Student number</label>
                                            <input class="form-control form-control-small" id="student-number-{{ $student->id }}" name="student_number" value="{{ $student->student_number }}" maxlength="30">
                                            <label class="field-label" for="first-name-{{ $student->id }}">First name</label>
                                            <input class="form-control form-control-small" id="first-name-{{ $student->id }}" name="first_name" value="{{ $student->first_name }}" required>
                                            <label class="field-label" for="last-name-{{ $student->id }}">Last name</label>
                                            <input class="form-control form-control-small" id="last-name-{{ $student->id }}" name="last_name" value="{{ $student->last_name }}" required>
                                            <label class="field-label" for="email-{{ $student->id }}">Email</label>
                                            <input class="form-control form-control-small" id="email-{{ $student->id }}" name="email" type="email" value="{{ $student->email }}" required>
                                            <button class="button button-primary button-small" type="submit">Save profile</button>
                                        </form>
                                        <form method="post" action="{{ route('registrar.students.access', $student) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="button button-quiet button-small" type="submit">{{ $student->is_active ? 'Deactivate account' : 'Restore access' }}</button>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-cell">No students have registered yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination-row">{{ $students->links() }}</div>
    </section>
@endsection
