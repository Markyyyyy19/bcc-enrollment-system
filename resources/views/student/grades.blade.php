@extends('layouts.app')

@section('title', 'My grades')
@section('eyebrow', 'Student portal / Grades')

@section('content')
    <section class="page-heading-row"><div><p class="section-kicker">Your academic record</p><h1 class="page-title">My grades<span class="title-period">.</span></h1><p class="page-lede">View the latest grade entries shared by your instructors.</p></div></section>
    <section class="panel list-panel">
        @if ($grades->isNotEmpty())
            <div class="table-wrap"><table class="responsive-table"><thead><tr><th>Subject</th><th>Program</th><th>Term</th><th>Prelim</th><th>Midterm</th><th>Final</th><th>Remarks</th></tr></thead><tbody>
                @foreach ($grades as $grade)
                    <tr>
                        <td data-label="Subject"><strong>{{ $grade->code }} · {{ $grade->title }}</strong><small>{{ $grade->units }} units</small></td>
                        <td data-label="Program">{{ $grade->program_code }}</td>
                        <td data-label="Term">{{ $grade->school_year }} · {{ $grade->semester }}</td>
                        <td data-label="Prelim">{{ $grade->preliminary ?? '—' }}</td>
                        <td data-label="Midterm">{{ $grade->midterm ?? '—' }}</td>
                        <td data-label="Final">{{ $grade->final_grade ?? '—' }}</td>
                        <td data-label="Remarks"><span class="status-badge status-{{ strtolower(str_replace(' ', '-', $grade->remarks)) }}">{{ $grade->remarks }}</span></td>
                    </tr>
                @endforeach
            </tbody></table></div>
        @else
            <div class="empty-state"><span class="empty-mark" aria-hidden="true">↗</span><h2>No grades have been posted yet.</h2><p>When an instructor records a grade for your enrolled subjects, it will appear here.</p></div>
        @endif
    </section>
@endsection
