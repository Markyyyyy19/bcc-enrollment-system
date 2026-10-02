<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EnrollmentResource;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class StudentController extends Controller
{
    public function me(): StudentResource
    {
        return new StudentResource(Auth::user());
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'string', 'max:120'],
            'active' => ['sometimes', 'boolean'],
            'program_id' => ['sometimes', 'integer', 'exists:programs,id'],
            'major_id' => ['sometimes', 'integer', 'exists:program_majors,id'],
            'school_year' => ['sometimes', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['sometimes', 'in:1st,2nd,Summer'],
            'year_level' => ['sometimes', 'integer', 'between:1,6'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $query = User::query()->where('role', 'student');

        if (isset($filters['program_id']) || isset($filters['major_id']) || isset($filters['school_year']) || isset($filters['semester']) || isset($filters['year_level'])) {
            $studentIds = DB::table('enrollments')
                ->select('student_id')
                ->when(isset($filters['program_id']), fn ($builder) => $builder->where('program_id', $filters['program_id']))
                ->when(isset($filters['major_id']), fn ($builder) => $builder->where('major_id', $filters['major_id']))
                ->when(isset($filters['school_year']), fn ($builder) => $builder->where('school_year', $filters['school_year']))
                ->when(isset($filters['semester']), fn ($builder) => $builder->where('semester', $filters['semester']))
                ->when(isset($filters['year_level']), fn ($builder) => $builder->where('year_level', $filters['year_level']));
            $query->whereIn('id', $studentIds);
        }

        $students = $query
            ->when(isset($filters['search']), function ($query) use ($filters): void {
                $search = '%'.$filters['search'].'%';
                $query->where(function ($nested) use ($search): void {
                    $nested->where('student_number', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->when(array_key_exists('active', $filters), fn ($query) => $query->where('is_active', $filters['active']))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();

        return StudentResource::collection($students);
    }

    public function users(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'role' => ['sometimes', 'in:student,registrar'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        return StudentResource::collection(
            User::query()
                ->when(isset($filters['role']), fn ($query) => $query->where('role', $filters['role']))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate($filters['per_page'] ?? 25)
                ->withQueryString(),
        );
    }

    public function show(User $student): StudentResource
    {
        abort_unless($student->role === 'student', Response::HTTP_NOT_FOUND);

        return new StudentResource($student);
    }

    public function lookup(string $studentNumber): StudentResource
    {
        $student = User::query()
            ->where('role', 'student')
            ->where('student_number', $studentNumber)
            ->firstOrFail();

        return new StudentResource($student);
    }

    public function enrollments(User $student): AnonymousResourceCollection
    {
        $this->authorizeStudentRead($student);

        return EnrollmentResource::collection(
            Enrollment::query()
                ->where('student_id', $student->id)
                ->with(['student', 'program', 'major', 'subjects.program', 'subjects.major'])
                ->latest('submitted_at')
                ->paginate(25),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_number' => ['nullable', 'string', 'min:4', 'max:30', 'unique:users,student_number'],
            'first_name' => ['required', 'string', 'min:2', 'max:80'],
            'last_name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ]);

        $student = User::query()->create([
            'student_number' => $validated['student_number'] ?? null,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'role' => 'student',
            'is_active' => true,
        ]);

        return (new StudentResource($student))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(Request $request, User $student): StudentResource
    {
        abort_unless($student->role === 'student', Response::HTTP_NOT_FOUND);
        $validated = $request->validate([
            'student_number' => ['nullable', 'string', 'min:4', 'max:30', 'unique:users,student_number,'.$student->id],
            'first_name' => ['required', 'string', 'min:2', 'max:80'],
            'last_name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email,'.$student->id],
        ]);
        $student->update($validated);

        return new StudentResource($student->refresh());
    }

    public function toggleAccess(User $student): StudentResource
    {
        abort_unless($student->role === 'student', Response::HTTP_NOT_FOUND);
        $student->update(['is_active' => ! $student->is_active]);

        return new StudentResource($student->refresh());
    }

    private function authorizeStudentRead(User $student): void
    {
        abort_unless($student->role === 'student', Response::HTTP_NOT_FOUND);
        abort_unless(Auth::user()->role === 'registrar' || Auth::id() === $student->id, Response::HTTP_NOT_FOUND);
    }
}
