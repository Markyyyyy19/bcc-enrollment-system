<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
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
            'program_id' => $this->program_id,
            'major_id' => $this->major_id,
            'code' => $this->code,
            'title' => $this->title,
            'units' => (float) $this->units,
            'year_level' => $this->year_level,
            'semester' => $this->semester,
            'is_capstone' => $this->is_capstone,
            'is_active' => $this->is_active,
            'program' => $this->whenLoaded('program', fn () => [
                'id' => $this->program->id,
                'code' => $this->program->code,
                'name' => $this->program->name,
            ]),
            'major' => $this->whenLoaded('major', fn () => $this->major ? [
                'id' => $this->major->id,
                'name' => $this->major->name,
            ] : null),
        ];
    }
}
