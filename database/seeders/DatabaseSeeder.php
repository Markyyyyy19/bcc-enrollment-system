<?php

namespace Database\Seeders;

use App\Services\CurriculumImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::unprepared(file_get_contents(database_path('sql/seed.postgresql.sql')));
        app(CurriculumImporter::class)->import();
        DB::table('users')->where('email', 'registrar@bcc.edu.ph')->update(['password_hash' => Hash::make('Registrar123!')]);
        DB::table('users')->where('email', 'student@bcc.edu.ph')->update(['password_hash' => Hash::make('Student123!')]);
    }
}
