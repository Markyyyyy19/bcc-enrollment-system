<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'program_id' => $this->program_id,
            'major_id' => $this->major_id,
            'school_year' => $this->school_year,
            'semester' => $this->semester,
            'year_level' => $this->year_level,
            'student_type' => $this->student_type,
            'status' => $this->status,
            'registrar_note' => $this->registrar_note,
            'submitted_at' => $this->submitted_at,
            'reviewed_at' => $this->reviewed_at,
            'student' => $this->whenLoaded('student', fn () => new StudentResource($this->student)),
            'program' => $this->whenLoaded('program', fn () => new ProgramResource($this->program)),
            'major' => $this->whenLoaded('major', fn () => $this->major ? [
                'id' => $this->major->id,
                'name' => $this->major->name,
            ] : null),
            'subjects' => SubjectResource::collection($this->whenLoaded('subjects')),
        ];
    }
}
