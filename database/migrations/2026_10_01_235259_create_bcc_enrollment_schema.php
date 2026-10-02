<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/schema.postgresql.sql')));
    }

    public function down(): void
    {
        DB::unprepared('DROP TABLE IF EXISTS grades, enrollment_subjects, class_schedules, enrollments, subjects, program_majors, programs, users CASCADE');
        DB::unprepared('DROP FUNCTION IF EXISTS touch_updated_at() CASCADE');
    }
};
