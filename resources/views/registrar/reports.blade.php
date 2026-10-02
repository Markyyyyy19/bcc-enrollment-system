@extends('layouts.app')

@section('title', 'Enrollment reports')
@section('eyebrow', 'Registrar / Reports')

@section('content')
    <section class="page-heading-row"><div><p class="section-kicker">A clear record of the term</p><h1 class="page-title">Enrollment reports<span class="title-period">.</span></h1><p class="page-lede">A current snapshot of application activity across BCC programs.</p></div><a class="button button-primary" href="{{ route('registrar.reports.export') }}">Export CSV <span aria-hidden="true">⇩</span></a></section>
    <section class="metric-grid report-metrics" aria-label="Application totals">
        <article class="metric-card"><div class="metric-label">Applications <span class="metric-icon">▤</span></div><strong class="metric-value">{{ number_format($counts->applications ?? 0) }}</strong><span class="metric-note">All recorded terms</span></article>
        <article class="metric-card"><div class="metric-label">Submitted <span class="metric-icon metric-icon-warm">◷</span></div><strong class="metric-value">{{ number_format($counts->submitted ?? 0) }}</strong><span class="metric-note">Waiting for a first review</span></article>
        <article class="metric-card"><div class="metric-label">Under review <span class="metric-icon metric-icon-warm">◷</span></div><strong class="metric-value">{{ number_format($counts->under_review ?? 0) }}</strong><span class="metric-note">Being reviewed by the office</span></article>
        <article class="metric-card"><div class="metric-label">Approved <span class="metric-icon">✓</span></div><strong class="metric-value">{{ number_format($counts->approved ?? 0) }}</strong><span class="metric-note">Cleared for enrollment</span></article>
    </section>
    <section class="panel report-panel"><div class="panel-heading"><div><p class="section-kicker">By program</p><h2>Application distribution</h2></div></div>
        @if ($byProgram->isNotEmpty())
            <div class="table-wrap"><table class="responsive-table"><thead><tr><th>Program</th><th>Applications</th><th>Action</th></tr></thead><tbody>@foreach ($byProgram as $program)<tr><td data-label="Program"><strong>{{ $program->code }}</strong><small>{{ $program->name }}</small></td><td data-label="Applications">{{ $program->total }}</td><td data-label="Action"><a class="text-link" href="{{ route('registrar.applications.index') }}">View application queue <span aria-hidden="true">→</span></a></td></tr>@endforeach</tbody></table></div>
        @else
            <div class="empty-inline">Program totals will appear as applications are submitted.</div>
        @endif
        <p class="report-note">CSV exports include student details and should be handled only by authorized college staff.</p>
    </section>
@endsection
