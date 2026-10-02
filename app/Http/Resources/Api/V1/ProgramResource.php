<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgramResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'department' => $this->department,
            'duration_years' => $this->duration_years,
            'is_active' => $this->is_active,
            'majors' => $this->whenLoaded('majors', fn () => $this->majors->map(fn ($major): array => [
                'id' => $major->id,
                'name' => $major->name,
                'is_active' => $major->is_active,
            ])),
        ];
    }
}
