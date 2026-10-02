<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subject extends Model
{
    protected $table = 'subjects';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_capstone' => 'boolean'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(ProgramMajor::class, 'major_id');
    }
}
