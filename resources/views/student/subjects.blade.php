@extends('layouts.app')

@section('title', 'Subject catalog')
@section('eyebrow', 'Student portal / Subjects')

@section('content')
    <section class="page-heading-row"><div><p class="section-kicker">Explore the curriculum</p><h1 class="page-title">Subject catalog<span class="title-period">.</span></h1><p class="page-lede">Browse active subjects across Buenavista Community College programs.</p></div></section>
    <section class="panel list-panel">
        @if ($subjects->isNotEmpty())
            <div class="table-wrap"><table class="responsive-table"><thead><tr><th>Subject</th><th>Program</th><th>Year</th><th>Semester</th><th>Units</th></tr></thead><tbody>
                @foreach ($subjects as $subject)
                    <tr>
                        <td data-label="Subject"><strong>{{ $subject->code }}</strong><small>{{ $subject->title }}</small></td>
                        <td data-label="Program">{{ $subject->program?->code }}<small>{{ $subject->program?->name }}</small></td>
                        <td data-label="Year">{{ $subject->year_level }}{{ $subject->year_level === 1 ? 'st' : ($subject->year_level === 2 ? 'nd' : ($subject->year_level === 3 ? 'rd' : 'th')) }}</td>
                        <td data-label="Semester">{{ $subject->semester }}</td>
                        <td data-label="Units">{{ $subject->units }}</td>
                    </tr>
                @endforeach
            </tbody></table></div>
            <div class="pagination-row">{{ $subjects->links() }}</div>
        @else
            <div class="empty-state"><span class="empty-mark" aria-hidden="true">▧</span><h2>The catalog is being prepared.</h2><p>Active subjects will appear as soon as the academic catalog is published.</p></div>
        @endif
    </section>
@endsection
