<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrarController extends Controller
{
    public function index(Request $request): View
    {
        $statuses = ['submitted', 'under_review', 'approved', 'rejected', 'withdrawn'];
        $selectedStatus = $request->query('status', 'all');
        $query = Enrollment::query()->with(['student', 'program', 'major', 'subjects'])->latest('submitted_at');

        if (in_array($selectedStatus, $statuses, true)) {
            $query->where('status', $selectedStatus);
        } else {
            $selectedStatus = 'all';
        }

        return view('registrar.applications', [
            'enrollments' => $query->paginate(12)->withQueryString(),
            'selectedStatus' => $selectedStatus,
            'pendingCount' => Enrollment::query()->whereIn('status', ['submitted', 'under_review'])->count(),
        ]);
    }

    public function review(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:under_review,approved,rejected'],
            'registrar_note' => ['nullable', 'string', 'max:500'],
        ]);

        $enrollment->update($validated + [
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Application status updated.');
    }

    public function students(): View
    {
        $students = User::query()
            ->where('role', 'student')
            ->addSelect([
                'latest_program_code' => DB::table('enrollments')
                    ->join('programs', 'enrollments.program_id', '=', 'programs.id')
                    ->select('programs.code')
                    ->whereColumn('enrollments.student_id', 'users.id')
                    ->orderByDesc('enrollments.submitted_at')
                    ->limit(1),
                'latest_enrollment_status' => DB::table('enrollments')
                    ->select('status')
                    ->whereColumn('enrollments.student_id', 'users.id')
                    ->orderByDesc('enrollments.submitted_at')
                    ->limit(1),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return view('registrar.students', [
            'students' => $students,
        ]);
    }

    public function updateStudent(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);

        $validated = $request->validate([
            'student_number' => ['nullable', 'string', 'min:4', 'max:30', 'unique:users,student_number,'.$student->id],
            'first_name' => ['required', 'string', 'min:2', 'max:80'],
            'last_name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email,'.$student->id],
        ]);

        $student->update($validated);

        return back()->with('success', 'Student profile updated.');
    }

    public function toggleStudentAccess(User $student): RedirectResponse
    {
        abort_unless($student->role === 'student', 404);
        $student->update(['is_active' => ! $student->is_active]);

        return back()->with('success', $student->is_active ? 'Student access restored.' : 'Student access deactivated.');
    }

    public function programs(): View
    {
        return view('registrar.programs', [
            'programs' => Program::query()->withCount('subjects')->orderBy('code')->paginate(20),
        ]);
    }

    public function storeProgram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:programs,code'],
            'name' => ['required', 'string', 'max:160'],
            'department' => ['required', 'string', 'max:120'],
            'duration_years' => ['required', 'integer', 'between:1,6'],
        ]);

        Program::query()->create($validated + ['is_active' => true]);

        return back()->with('success', 'Program added to the catalog.');
    }

    public function updateProgram(Request $request, Program $program): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:programs,code,'.$program->id],
            'name' => ['required', 'string', 'max:160'],
            'department' => ['required', 'string', 'max:120'],
            'duration_years' => ['required', 'integer', 'between:1,6'],
        ]);

        $program->update($validated);

        return back()->with('success', 'Program details updated.');
    }

    public function toggleProgram(Program $program): RedirectResponse
    {
        $program->update(['is_active' => ! $program->is_active]);

        return back()->with('success', $program->is_active ? 'Program is now open for applications.' : 'Program is now archived.');
    }

    public function subjects(): View
    {
        return view('registrar.subjects', [
            'subjects' => Subject::query()->with('program')->orderBy('program_id')->orderBy('year_level')->orderBy('code')->paginate(30),
            'programs' => Program::query()->where('is_active', true)->orderBy('code')->get(),
            'majors' => DB::table('program_majors')
                ->join('programs', 'program_majors.program_id', '=', 'programs.id')
                ->where('program_majors.is_active', true)
                ->select('program_majors.*', 'programs.code as program_code')
                ->orderBy('program_majors.name')
                ->get(),
        ]);
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $validated = $this->validateSubject($request);
        $now = now();

        Subject::query()->create($validated + ['is_active' => true, 'is_capstone' => false, 'created_at' => $now, 'updated_at' => $now]);

        return back()->with('success', 'Subject added to the curriculum.');
    }

    public function updateSubject(Request $request, Subject $subject): RedirectResponse
    {
        $validated = $this->validateSubject($request, $subject);
        $subject->update($validated + ['updated_at' => now()]);

        return back()->with('success', 'Subject details updated.');
    }

    public function toggleSubject(Subject $subject): RedirectResponse
    {
        $subject->update(['is_active' => ! $subject->is_active, 'updated_at' => now()]);

        return back()->with('success', $subject->is_active ? 'Subject restored.' : 'Subject archived.');
    }

    private function validateSubject(Request $request, ?Subject $subject = null): array
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'major_id' => ['nullable', 'integer', 'exists:program_majors,id'],
            'code' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:255'],
            'units' => ['required', 'numeric', 'between:1,9'],
            'year_level' => ['required', 'integer', 'between:1,6'],
            'semester' => ['required', 'in:1st,2nd,Summer'],
        ]);

        $majorBelongsToProgram = empty($validated['major_id']) || DB::table('program_majors')
            ->where('id', $validated['major_id'])
            ->where('program_id', $validated['program_id'])
            ->where('is_active', true)
            ->exists();

        if (! $majorBelongsToProgram) {
            throw ValidationException::withMessages(['major_id' => 'Choose a major belonging to the selected program.']);
        }

        $duplicate = Subject::query()
            ->where('program_id', $validated['program_id'])
            ->where('code', $validated['code'])
            ->when($subject, fn ($query) => $query->where('id', '<>', $subject->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['code' => 'That subject code is already used in this program.']);
        }

        return $validated;
    }

    public function grades(Request $request): View
    {
        $studentId = $request->integer('student_id') ?: null;
        $gradeQuery = DB::table('enrollments')
            ->join('enrollment_subjects', 'enrollments.id', '=', 'enrollment_subjects.enrollment_id')
            ->join('users', 'enrollments.student_id', '=', 'users.id')
            ->join('programs', 'enrollments.program_id', '=', 'programs.id')
            ->join('subjects', 'enrollment_subjects.subject_id', '=', 'subjects.id')
            ->leftJoin('grades', function ($join): void {
                $join->on('grades.enrollment_id', '=', 'enrollment_subjects.enrollment_id')
                    ->on('grades.subject_id', '=', 'enrollment_subjects.subject_id');
            })
            ->where('enrollments.status', 'approved')
            ->when($studentId, fn ($query) => $query->where('enrollments.student_id', $studentId))
            ->select('enrollments.id as enrollment_id', 'enrollments.school_year', 'enrollments.semester', 'users.student_number', 'users.first_name', 'users.last_name', 'programs.code as program_code', 'subjects.id as subject_id', 'subjects.code', 'subjects.title', 'grades.preliminary', 'grades.midterm', 'grades.final_term', 'grades.final_grade', 'grades.remarks')
            ->orderBy('users.last_name')
            ->orderBy('subjects.code');
        $grades = $gradeQuery->paginate(30)->withQueryString();

        return view('registrar.grades', [
            'grades' => $grades,
            'students' => User::query()->where('role', 'student')->orderBy('last_name')->get(),
            'selectedStudentId' => $studentId,
        ]);
    }

    public function updateGrade(Request $request, Enrollment $enrollment, Subject $subject): RedirectResponse
    {
        $validated = $request->validate([
            'preliminary' => ['nullable', 'numeric', 'between:1,5'],
            'midterm' => ['nullable', 'numeric', 'between:1,5'],
            'final_term' => ['nullable', 'numeric', 'between:1,5'],
            'remarks' => ['required', 'in:In progress,Incomplete'],
        ]);

        $isEligible = DB::table('enrollment_subjects')
            ->join('enrollments', 'enrollment_subjects.enrollment_id', '=', 'enrollments.id')
            ->where('enrollment_subjects.enrollment_id', $enrollment->id)
            ->where('enrollment_subjects.subject_id', $subject->id)
            ->where('enrollments.status', 'approved')
            ->exists();

        abort_unless($isEligible, 404);

        $components = [$validated['preliminary'] ?? null, $validated['midterm'] ?? null, $validated['final_term'] ?? null];
        $complete = ! in_array(null, $components, true);
        $finalGrade = $complete ? round(array_sum($components) / 3, 2) : null;
        $remarks = $validated['remarks'] === 'Incomplete'
            ? 'Incomplete'
            : ($finalGrade === null ? 'In progress' : ($finalGrade <= 3 ? 'Passed' : 'Failed'));

        DB::table('grades')->updateOrInsert(
            ['enrollment_id' => $enrollment->id, 'subject_id' => $subject->id],
            [
                'preliminary' => $validated['preliminary'] ?? null,
                'midterm' => $validated['midterm'] ?? null,
                'final_term' => $validated['final_term'] ?? null,
                'final_grade' => $finalGrade,
                'remarks' => $remarks,
                'encoded_by' => auth()->id(),
                'updated_at' => now(),
            ],
        );

        return back()->with('success', 'Grade record updated.');
    }

    public function reports(): View
    {
        $counts = Enrollment::query()
            ->selectRaw("COUNT(*) AS applications, COUNT(*) FILTER (WHERE status = 'submitted') AS submitted, COUNT(*) FILTER (WHERE status = 'under_review') AS under_review, COUNT(*) FILTER (WHERE status = 'approved') AS approved, COUNT(*) FILTER (WHERE status = 'rejected') AS rejected")
            ->first();
        $byProgram = DB::table('enrollments')
            ->join('programs', 'enrollments.program_id', '=', 'programs.id')
            ->select('programs.code', 'programs.name', DB::raw('COUNT(enrollments.id) AS total'))
            ->groupBy('programs.id', 'programs.code', 'programs.name')
            ->orderBy('programs.code')
            ->get();

        return view('registrar.reports', compact('counts', 'byProgram'));
    }

    public function report(): StreamedResponse
    {
        $rows = DB::table('enrollments')
            ->join('users', 'enrollments.student_id', '=', 'users.id')
            ->join('programs', 'enrollments.program_id', '=', 'programs.id')
            ->select('users.student_number', 'users.first_name', 'users.last_name', 'users.email', 'programs.code as program', 'enrollments.school_year', 'enrollments.semester', 'enrollments.year_level', 'enrollments.student_type', 'enrollments.status')
            ->orderBy('enrollments.school_year')
            ->orderBy('users.last_name')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Student number', 'First name', 'Last name', 'Email', 'Program', 'School year', 'Semester', 'Year level', 'Student type', 'Status']);

            foreach ($rows as $row) {
                fputcsv($output, (array) $row);
            }

            fclose($output);
        }, 'bcc-enrollment-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
