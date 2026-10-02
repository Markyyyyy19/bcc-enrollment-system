<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProgramResource;
use App\Http\Resources\Api\V1\SubjectResource;
use App\Models\Program;
use App\Models\ProgramMajor;
use App\Models\Subject;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CatalogController extends Controller
{
    public function programs(): AnonymousResourceCollection
    {
        return ProgramResource::collection(
            Program::query()->with(['majors' => fn ($query) => $query->where('is_active', true)])
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
        );
    }

    public function majors(Program $program): JsonResponse
    {
        return response()->json([
            'data' => $program->majors()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'program_id', 'name']),
        ]);
    }

    public function subjects(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'program_id' => ['sometimes', 'integer', 'exists:programs,id'],
            'major_id' => ['sometimes', 'integer', 'exists:program_majors,id'],
            'year_level' => ['sometimes', 'integer', 'between:1,6'],
            'semester' => ['sometimes', 'in:1st,2nd,Summer'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $subjects = Subject::query()
            ->with(['program', 'major'])
            ->where('is_active', true)
            ->when(isset($filters['program_id']), fn ($query) => $query->where('program_id', $filters['program_id']))
            ->when(isset($filters['major_id']), fn ($query) => $query->where('major_id', $filters['major_id']))
            ->when(isset($filters['year_level']), fn ($query) => $query->where('year_level', $filters['year_level']))
            ->when(isset($filters['semester']), fn ($query) => $query->where('semester', $filters['semester']))
            ->orderBy('program_id')
            ->orderBy('year_level')
            ->orderBy('code')
            ->paginate($filters['per_page'] ?? 50)
            ->withQueryString();

        return SubjectResource::collection($subjects);
    }

    public function schedules(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'school_year' => ['sometimes', 'regex:/^\d{4}-\d{4}$/'],
            'semester' => ['sometimes', 'in:1st,2nd,Summer'],
            'program_id' => ['sometimes', 'integer', 'exists:programs,id'],
            'major_id' => ['sometimes', 'integer', 'exists:program_majors,id'],
            'year_level' => ['sometimes', 'integer', 'between:1,6'],
            'section' => ['sometimes', 'string', 'max:20'],
            'instructor' => ['sometimes', 'string', 'max:160'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $schedules = $this->scheduleQuery($filters)
            ->orderBy('class_schedules.school_year')
            ->orderBy('class_schedules.semester')
            ->orderBy('class_schedules.section')
            ->orderBy('subjects.code')
            ->paginate($filters['per_page'] ?? 50)
            ->withQueryString();

        return response()->json($schedules);
    }

    public function instructorSubjects(Request $request, ?string $instructor = null): JsonResponse
    {
        if ($instructor !== null) {
            $request->merge(['instructor' => $instructor]);
        }

        return $this->schedules($request);
    }

    public function classSubjects(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'semester' => ['required', 'in:1st,2nd,Summer'],
            'school_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'major_id' => ['nullable', 'integer', 'exists:program_majors,id'],
            'section' => ['required', 'string', 'max:20'],
            'year_level' => ['required', 'integer', 'between:1,6'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $schedules = $this->scheduleQuery($filters)
            ->where('class_schedules.semester', $filters['semester'])
            ->where('class_schedules.school_year', $filters['school_year'])
            ->where('class_schedules.program_id', $filters['program_id'])
            ->where('class_schedules.section', $filters['section'])
            ->where('class_schedules.year_level', $filters['year_level'])
            ->when(isset($filters['major_id']), fn ($query) => $query->where('subjects.major_id', $filters['major_id']))
            ->orderBy('subjects.code')
            ->paginate($filters['per_page'] ?? 50)
            ->withQueryString();

        return response()->json($schedules);
    }

    public function storeProgram(Request $request): JsonResponse
    {
        $program = Program::query()->create($request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:programs,code'],
            'name' => ['required', 'string', 'max:160'],
            'department' => ['required', 'string', 'max:120'],
            'duration_years' => ['required', 'integer', 'between:1,6'],
        ]) + ['is_active' => true]);

        return (new ProgramResource($program))->response()->setStatusCode(201);
    }

    public function updateProgram(Request $request, Program $program): ProgramResource
    {
        $program->update($request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:programs,code,'.$program->id],
            'name' => ['required', 'string', 'max:160'],
            'department' => ['required', 'string', 'max:120'],
            'duration_years' => ['required', 'integer', 'between:1,6'],
        ]));

        return new ProgramResource($program->refresh());
    }

    public function toggleProgram(Program $program): ProgramResource
    {
        $program->update(['is_active' => ! $program->is_active]);

        return new ProgramResource($program->refresh());
    }

    public function storeSubject(Request $request): JsonResponse
    {
        $subject = Subject::query()->create($this->validateSubject($request) + [
            'is_active' => true,
            'is_capstone' => $request->boolean('is_capstone'),
        ]);

        return (new SubjectResource($subject->load(['program', 'major'])))->response()->setStatusCode(201);
    }

    public function updateSubject(Request $request, Subject $subject): SubjectResource
    {
        $subject->update($this->validateSubject($request) + [
            'is_capstone' => $request->boolean('is_capstone'),
        ]);

        return new SubjectResource($subject->refresh()->load(['program', 'major']));
    }

    public function toggleSubject(Subject $subject): SubjectResource
    {
        $subject->update(['is_active' => ! $subject->is_active]);

        return new SubjectResource($subject->refresh());
    }

    public function bulkSubjects(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subjects' => ['required', 'array', 'min:1', 'max:500'],
            'subjects.*.program_id' => ['required', 'integer', 'exists:programs,id'],
            'subjects.*.major_id' => ['nullable', 'integer', 'exists:program_majors,id'],
            'subjects.*.code' => ['required', 'string', 'max:40'],
            'subjects.*.title' => ['required', 'string', 'max:255'],
            'subjects.*.units' => ['required', 'numeric', 'between:1,9'],
            'subjects.*.year_level' => ['required', 'integer', 'between:1,6'],
            'subjects.*.semester' => ['required', 'in:1st,2nd,Summer'],
            'subjects.*.is_capstone' => ['sometimes', 'boolean'],
        ]);

        $rows = DB::transaction(function () use ($validated): array {
            $saved = [];

            foreach ($validated['subjects'] as $row) {
                $this->assertMajorBelongsToProgram($row['program_id'], $row['major_id'] ?? null);
                $subject = Subject::query()->updateOrCreate(
                    ['program_id' => $row['program_id'], 'code' => $row['code']],
                    $row + ['is_active' => true, 'is_capstone' => $row['is_capstone'] ?? false],
                );
                $saved[] = $subject;
            }

            return $saved;
        });

        return response()->json([
            'data' => SubjectResource::collection(collect($rows)->map->load(['program', 'major'])),
            'count' => count($rows),
        ], 201);
    }

    private function validateSubject(Request $request): array
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'major_id' => ['nullable', 'integer', 'exists:program_majors,id'],
            'code' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:255'],
            'units' => ['required', 'numeric', 'between:1,9'],
            'year_level' => ['required', 'integer', 'between:1,6'],
            'semester' => ['required', 'in:1st,2nd,Summer'],
            'is_capstone' => ['sometimes', 'boolean'],
        ]);

        $this->assertMajorBelongsToProgram($validated['program_id'], $validated['major_id'] ?? null);

        $duplicate = Subject::query()
            ->where('program_id', $validated['program_id'])
            ->where('code', $validated['code'])
            ->when($request->route('subject') instanceof Subject, fn ($query) => $query->where('id', '<>', $request->route('subject')->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['code' => 'That subject code is already used in this program.']);
        }

        return $validated;
    }

    private function assertMajorBelongsToProgram(int $programId, ?int $majorId): void
    {
        if ($majorId !== null && ! ProgramMajor::query()
            ->whereKey($majorId)
            ->where('program_id', $programId)
            ->where('is_active', true)
            ->exists()) {
            throw ValidationException::withMessages(['major_id' => 'Choose an active major belonging to the selected program.']);
        }
    }

    private function scheduleQuery(array $filters): Builder
    {
        return DB::table('class_schedules')
            ->join('subjects', 'class_schedules.subject_id', '=', 'subjects.id')
            ->join('programs', 'class_schedules.program_id', '=', 'programs.id')
            ->where('class_schedules.is_active', true)
            ->where('subjects.is_active', true)
            ->where('programs.is_active', true)
            ->select(
                'class_schedules.id',
                'class_schedules.school_year',
                'class_schedules.semester',
                'class_schedules.year_level',
                'class_schedules.section',
                'class_schedules.instructor_name',
                'class_schedules.days',
                'class_schedules.start_time',
                'class_schedules.end_time',
                'class_schedules.room',
                'class_schedules.capacity',
                'programs.id as program_id',
                'programs.code as program_code',
                'subjects.id as subject_id',
                'subjects.code as subject_code',
                'subjects.title as subject_title',
                'subjects.units',
            )
            ->when(isset($filters['school_year']), fn ($query) => $query->where('class_schedules.school_year', $filters['school_year']))
            ->when(isset($filters['semester']), fn ($query) => $query->where('class_schedules.semester', $filters['semester']))
            ->when(isset($filters['program_id']), fn ($query) => $query->where('class_schedules.program_id', $filters['program_id']))
            ->when(isset($filters['year_level']), fn ($query) => $query->where('class_schedules.year_level', $filters['year_level']))
            ->when(isset($filters['section']), fn ($query) => $query->where('class_schedules.section', $filters['section']))
            ->when(isset($filters['major_id']), fn ($query) => $query->where('subjects.major_id', $filters['major_id']))
            ->when(isset($filters['instructor']), fn ($query) => $query->where('class_schedules.instructor_name', 'like', '%'.$filters['instructor'].'%'));
    }
}
