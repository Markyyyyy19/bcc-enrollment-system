@extends('layouts.app')

@section('title', 'Applications')
@section('eyebrow', 'Registrar / Applications')

@section('content')
    <section class="page-heading-row"><div><h1 class="page-title">Applications</h1><p class="page-lede">Review and manage student enrollment.</p></div><span class="queue-count"><strong>{{ $pendingCount }}</strong><span>awaiting review</span></span></section>
    <section class="panel list-panel">
        <div class="filter-row">@foreach (['all' => 'All applications', 'submitted' => 'Submitted', 'under_review' => 'Under review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn'] as $status => $label)<a class="filter-chip {{ $selectedStatus === $status ? 'is-selected' : '' }}" href="{{ route('registrar.applications.index', ['status' => $status]) }}" @if($selectedStatus === $status) aria-current="page" @endif>{{ $label }}</a>@endforeach</div>
        @if ($enrollments->isNotEmpty())
            <div class="table-wrap"><table class="responsive-table applications-table registrar-applications"><thead><tr><th>Student</th><th>Program & term</th><th>Submitted</th><th>Status</th><th>Review</th></tr></thead><tbody>
                @foreach ($enrollments as $enrollment)
                    <tr><td data-label="Student"><strong>{{ $enrollment->student?->first_name }} {{ $enrollment->student?->last_name }}</strong><small>{{ $enrollment->student?->student_number }} · {{ $enrollment->student?->email }}</small></td><td data-label="Program & term"><strong>{{ $enrollment->program?->code }}{{ $enrollment->major?->name ? ' · '.$enrollment->major->name : '' }}</strong><small>{{ $enrollment->school_year }} · {{ $enrollment->semester }} · Year {{ $enrollment->year_level }} · {{ $enrollment->subjects->count() }} subjects</small></td><td data-label="Submitted">{{ $enrollment->submitted_at?->format('M j, Y') }}</td><td data-label="Status"><span class="status-badge status-{{ $enrollment->status }}">{{ str_replace('_', ' ', $enrollment->status) }}</span>@if($enrollment->registrar_note)<small>{{ $enrollment->registrar_note }}</small>@endif</td><td data-label="Review"><details class="review-details"><summary>{{ in_array($enrollment->status, ['submitted', 'under_review'], true) ? 'Review' : 'Update' }}</summary><form class="review-form" method="post" action="{{ route('registrar.applications.review', $enrollment) }}">@csrf @method('PATCH')<label class="field-label" for="status-{{ $enrollment->id }}">New status</label><select class="form-control form-control-small" id="status-{{ $enrollment->id }}" name="status"><option value="under_review" @selected($enrollment->status === 'under_review')>Under review</option><option value="approved" @selected($enrollment->status === 'approved')>Approved</option><option value="rejected" @selected($enrollment->status === 'rejected')>Rejected</option></select><label class="field-label" for="note-{{ $enrollment->id }}">Note to student</label><input class="form-control form-control-small" id="note-{{ $enrollment->id }}" name="registrar_note" value="{{ $enrollment->registrar_note }}" maxlength="500" placeholder="Optional update"><button class="button button-primary button-small" type="submit">Save review</button></form></details></td></tr>
                @endforeach
            </tbody></table></div>
            @if ($enrollments->hasPages())<div class="pagination-row">{{ $enrollments->links() }}</div>@endif
        @else
            <div class="empty-state"><span class="empty-mark"><x-portal-icon name="applications" /></span><h2>No applications in this view</h2><p>New applications will appear here. Try another filter to see more.</p></div>
        @endif
    </section>
@endsection
