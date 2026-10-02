<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $table = 'programs';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'duration_years' => 'integer'];
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    public function majors(): HasMany
    {
        return $this->hasMany(ProgramMajor::class);
    }
}
