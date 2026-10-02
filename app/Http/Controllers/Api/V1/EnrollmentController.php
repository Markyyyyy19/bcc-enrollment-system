<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EnrollmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'student_id' => ['sometimes', 'integer'],
            'program_id' => ['sometimes', 'integer', 'exists:programs,id'],
            'major_id' => ['sometimes', 'integer', 'exists:program_majors,id'],
            'school_year' => ['sometimes', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['sometimes', 'in:1st,2nd,Summer'],
            'year_level' => ['sometimes', 'integer', 'between:1,6'],
            'status' => ['sometimes', 'in:draft,submitted,under_review,approved,rejected,withdrawn'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $query = Enrollment::query()->with(['student', 'program', 'major', 'subjects']);

        if (Auth::user()->role === 'student') {
            $query->where('student_id', Auth::id());
        } elseif (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        $enrollments = $query
            ->when(isset($filters['program_id']), fn ($builder) => $builder->where('program_id', $filters['program_id']))
            ->when(isset($filters['major_id']), fn ($builder) => $builder->where('major_id', $filters['major_id']))
            ->when(isset($filters['school_year']), fn ($builder) => $builder->where('school_year', $filters['school_year']))
            ->when(isset($filters['semester']), fn ($builder) => $builder->where('semester', $filters['semester']))
            ->when(isset($filters['year_level']), fn ($builder) => $builder->where('year_level', $filters['year_level']))
            ->when(isset($filters['status']), fn ($builder) => $builder->where('status', $filters['status']))
            ->latest('submitted_at')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();

        return EnrollmentResource::collection($enrollments);
    }

    public function show(Enrollment $enrollment): EnrollmentResource
    {
        $this->authorizeRead($enrollment);

        return new EnrollmentResource($enrollment->load(['student', 'program', 'major', 'subjects.program', 'subjects.major']));
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['status' => $request->input('status', 'submitted')]);
        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'major_id' => ['nullable', 'integer', 'exists:program_majors,id'],
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', 'in:1st,2nd,Summer'],
            'year_level' => ['required', 'integer', 'between:1,6'],
            'student_type' => ['required', 'in:New,Continuing,Transferee,Returning'],
            'status' => ['required', 'in:draft,submitted'],
            'subject_ids' => ['required_if:status,submitted', 'sometimes', 'array', 'min:1', 'max:30'],
            'subject_ids.*' => ['required', 'integer', 'distinct', 'exists:subjects,id'],
        ]);

        $program = Program::query()->whereKey($validated['program_id'])->where('is_active', true)->first();

        if (! $program) {
            throw ValidationException::withMessages(['program_id' => 'Choose an active program.']);
        }

        $this->assertMajorSelection($program, $validated['major_id'] ?? null);
        $subjectIds = $validated['subject_ids'] ?? [];
        $this->assertSelectableSubjects($subjectIds, $validated);

        try {
            $enrollment = DB::transaction(function () use ($validated, $subjectIds): Enrollment {
                $enrollment = Enrollment::query()->create([
                    'student_id' => Auth::id(),
                    'program_id' => $validated['program_id'],
                    'major_id' => $validated['major_id'] ?? null,
                    'school_year' => $validated['school_year'],
                    'semester' => $validated['semester'],
                    'year_level' => $validated['year_level'],
                    'student_type' => $validated['student_type'],
                    'status' => $validated['status'],
                    'submitted_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($subjectIds !== []) {
                    $enrollment->subjects()->sync($subjectIds);
                }

                return $enrollment;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505') {
                return response()->json(['message' => 'An enrollment already exists for this term.'], Response::HTTP_CONFLICT);
            }

            throw $exception;
        }

        return (new EnrollmentResource($enrollment->load(['student', 'program', 'major', 'subjects.program', 'subjects.major'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function count(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'student_id' => ['sometimes', 'integer'],
            'program_id' => ['sometimes', 'integer', 'exists:programs,id'],
            'school_year' => ['sometimes', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['sometimes', 'in:1st,2nd,Summer'],
        ]);

        $query = Enrollment::query();

        if (Auth::user()->role === 'student') {
            $query->where('student_id', Auth::id());
        } elseif (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        $query->when(isset($filters['program_id']), fn ($builder) => $builder->where('program_id', $filters['program_id']))
            ->when(isset($filters['school_year']), fn ($builder) => $builder->where('school_year', $filters['school_year']))
            ->when(isset($filters['semester']), fn ($builder) => $builder->where('semester', $filters['semester']));

        $total = (clone $query)->count();

        return response()->json([
            'data' => [
                'total' => $total,
                'pending' => (clone $query)->whereIn('status', ['submitted', 'under_review'])->count(),
                'approved' => (clone $query)->where('status', 'approved')->count(),
                'rejected' => (clone $query)->where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function byStudentSubject(Request $request, User $student): AnonymousResourceCollection
    {
        $this->authorizeStudentRead($student);
        $filters = $request->validate([
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['required', 'in:1st,2nd,Summer'],
            'subject_id' => ['sometimes', 'integer', 'exists:subjects,id'],
        ]);

        $enrollments = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('school_year', $filters['school_year'])
            ->where('semester', $filters['semester'])
            ->when(isset($filters['subject_id']), fn ($query) => $query->whereHas('subjects', fn ($subjects) => $subjects->whereKey($filters['subject_id'])))
            ->with(['student', 'program', 'major', 'subjects.program', 'subjects.major'])
            ->latest('submitted_at')
            ->get();

        return EnrollmentResource::collection($enrollments);
    }

    public function addSubjects(Request $request, Enrollment $enrollment): EnrollmentResource
    {
        $this->authorizeEditable($enrollment);
        $validated = $request->validate([
            'subject_ids' => ['required', 'array', 'min:1', 'max:30'],
            'subject_ids.*' => ['required', 'integer', 'distinct', 'exists:subjects,id'],
        ]);
        $this->assertSelectableSubjects($validated['subject_ids'], $enrollment->only([
            'program_id', 'major_id', 'year_level', 'semester',
        ]));

        $enrollment->subjects()->syncWithoutDetaching($validated['subject_ids']);

        return new EnrollmentResource($enrollment->refresh()->load(['student', 'program', 'major', 'subjects.program', 'subjects.major']));
    }

    public function updateSubjects(Request $request, Enrollment $enrollment): EnrollmentResource
    {
        $this->authorizeEditable($enrollment);
        $validated = $request->validate([
            'subject_ids' => ['required', 'array', 'min:1', 'max:30'],
            'subject_ids.*' => ['required', 'integer', 'distinct', 'exists:subjects,id'],
        ]);
        $this->assertSelectableSubjects($validated['subject_ids'], $enrollment->only([
            'program_id', 'major_id', 'year_level', 'semester',
        ]));

        $enrollment->subjects()->sync($validated['subject_ids']);

        return new EnrollmentResource($enrollment->refresh()->load(['student', 'program', 'major', 'subjects.program', 'subjects.major']));
    }

    public function deleteSubject(Enrollment $enrollment, Subject $subject): EnrollmentResource
    {
        $this->authorizeEditable($enrollment);
        abort_unless($enrollment->subjects()->whereKey($subject->id)->exists(), Response::HTTP_NOT_FOUND);
        $enrollment->subjects()->detach($subject->id);

        return new EnrollmentResource($enrollment->refresh()->load(['student', 'program', 'major', 'subjects.program', 'subjects.major']));
    }

    public function submit(Enrollment $enrollment): EnrollmentResource
    {
        $this->authorizeEditable($enrollment);
        abort_unless($enrollment->subjects()->exists(), Response::HTTP_UNPROCESSABLE_ENTITY, 'Add at least one subject before submitting.');

        $enrollment->update(['status' => 'submitted', 'submitted_at' => now(), 'updated_at' => now()]);

        return new EnrollmentResource($enrollment->refresh()->load(['student', 'program', 'major', 'subjects.program', 'subjects.major']));
    }

    public function withdraw(Enrollment $enrollment): JsonResponse
    {
        $this->authorizeStudentOwner($enrollment);

        if (! in_array($enrollment->status, ['draft', 'submitted', 'under_review'], true)) {
            return response()->json(['message' => 'Only active applications can be withdrawn.'], Response::HTTP_CONFLICT);
        }

        $enrollment->update(['status' => 'withdrawn', 'updated_at' => now()]);

        return response()->json(['data' => new EnrollmentResource($enrollment->refresh()->load(['student', 'program', 'major', 'subjects']))]);
    }

    public function review(Request $request, Enrollment $enrollment): JsonResponse
    {
        if (! in_array($enrollment->status, ['submitted', 'under_review'], true)) {
            return response()->json(['message' => 'This application is not available for review.'], Response::HTTP_CONFLICT);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:under_review,approved,rejected'],
            'registrar_note' => ['nullable', 'string', 'max:500'],
        ]);

        $enrollment->update($validated + ['reviewed_at' => now(), 'updated_at' => now()]);

        return response()->json(['data' => new EnrollmentResource($enrollment->refresh()->load(['student', 'program', 'major', 'subjects']))]);
    }

    public function forward(Enrollment $enrollment): JsonResponse
    {
        abort_unless($enrollment->status === 'submitted', Response::HTTP_CONFLICT, 'Only submitted applications can be forwarded for review.');
        $enrollment->update(['status' => 'under_review', 'updated_at' => now()]);

        return response()->json(['data' => new EnrollmentResource($enrollment->refresh()->load(['student', 'program', 'major', 'subjects']))]);
    }

    public function returnToStudent(Request $request, Enrollment $enrollment): JsonResponse
    {
        abort_unless(in_array($enrollment->status, ['submitted', 'under_review'], true), Response::HTTP_CONFLICT, 'Only pending applications can be returned.');
        $validated = $request->validate(['registrar_note' => ['required', 'string', 'max:500']]);
        $enrollment->update([
            'status' => 'draft',
            'registrar_note' => $validated['registrar_note'],
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['data' => new EnrollmentResource($enrollment->refresh()->load(['student', 'program', 'major', 'subjects']))]);
    }

    private function assertMajorSelection(Program $program, ?int $majorId): void
    {
        $majors = $program->majors()->where('is_active', true)->get(['id']);

        if ($majors->isNotEmpty() && ! $majors->contains('id', $majorId)) {
            throw ValidationException::withMessages(['major_id' => 'Choose an active major for this program.']);
        }

        if ($majors->isEmpty() && $majorId !== null) {
            throw ValidationException::withMessages(['major_id' => 'This program does not have an active major.']);
        }
    }

    private function assertSelectableSubjects(array $subjectIds, array $enrollmentData): void
    {
        if ($subjectIds === []) {
            return;
        }

        $subjectCount = Subject::query()
            ->whereIn('id', $subjectIds)
            ->where('program_id', $enrollmentData['program_id'])
            ->where('year_level', $enrollmentData['year_level'])
            ->where('semester', $enrollmentData['semester'])
            ->where('is_active', true)
            ->when($enrollmentData['major_id'] !== null, fn ($query) => $query->where('major_id', $enrollmentData['major_id']))
            ->when($enrollmentData['major_id'] === null, fn ($query) => $query->whereNull('major_id'))
            ->count();

        if ($subjectCount !== count($subjectIds)) {
            throw ValidationException::withMessages(['subject_ids' => 'Choose only active subjects from the selected program, major, year, and semester.']);
        }
    }

    private function authorizeRead(Enrollment $enrollment): void
    {
        abort_unless(Auth::user()->role === 'registrar' || $enrollment->student_id === Auth::id(), Response::HTTP_NOT_FOUND);
    }

    private function authorizeStudentRead(User $student): void
    {
        abort_unless($student->role === 'student', Response::HTTP_NOT_FOUND);
        abort_unless(Auth::user()->role === 'registrar' || $student->id === Auth::id(), Response::HTTP_NOT_FOUND);
    }

    private function authorizeStudentOwner(Enrollment $enrollment): void
    {
        abort_unless(Auth::user()->role === 'student' && $enrollment->student_id === Auth::id(), Response::HTTP_NOT_FOUND);
    }

    private function authorizeEditable(Enrollment $enrollment): void
    {
        $this->authorizeStudentOwner($enrollment);
        abort_unless($enrollment->status === 'draft', Response::HTTP_CONFLICT, 'Only draft applications can be edited.');
    }
}
