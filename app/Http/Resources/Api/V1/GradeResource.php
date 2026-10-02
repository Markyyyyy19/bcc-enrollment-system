<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'enrollment_id' => $this->enrollment_id,
            'student_id' => $this->student_id,
            'student_number' => $this->student_number,
            'student_name' => trim($this->first_name.' '.$this->last_name),
            'program_code' => $this->program_code,
            'school_year' => $this->school_year,
            'semester' => $this->semester,
            'subject_id' => $this->subject_id,
            'subject_code' => $this->code,
            'subject_title' => $this->title,
            'preliminary' => $this->preliminary,
            'midterm' => $this->midterm,
            'final_term' => $this->final_term,
            'final_grade' => $this->final_grade,
            'remarks' => $this->remarks,
        ];
    }
}
