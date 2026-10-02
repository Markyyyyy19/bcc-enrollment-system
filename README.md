# BCC Online Enrollment

A Laravel and PostgreSQL enrollment portal for Buenavista Community College. The interface is server-rendered with Blade and plain CSS; Node.js and a frontend build step are not required.

## Requirements

- PHP 8.3 or later with `pdo_pgsql` and `fileinfo`
- PostgreSQL
- Composer

## Run locally

1. Create a PostgreSQL database named `bcc_enrollment` (for example, `psql -U postgres -c "CREATE DATABASE bcc_enrollment;"`) and configure `backend/.env` from `backend/.env.example`.
2. From `backend/`, install PHP dependencies with `composer install`.
3. Generate the application key with `php artisan key:generate`.
4. Create the application tables and demo records with `php -d extension=pdo_pgsql -d extension=fileinfo artisan migrate --seed`.
5. Start the local server from `backend/` with `php -d extension=pdo_pgsql -d extension=fileinfo -S 127.0.0.1:8000 -t public dev-router.php` (or `composer run dev`).
6. Open `http://127.0.0.1:8000`.

On Windows, if the PHP extensions are already enabled in `php.ini`, the `-d extension=...` flags can be omitted. Set the database host, port, name, username, and password in `backend/.env` before migrating.

## Demo accounts

- Registrar: `registrar@bcc.edu.ph` / `Registrar123!`
- Student: `student@bcc.edu.ph` / `Student123!`

These accounts are for local demonstrations only. Change or remove them before deploying a real enrollment system.

## Laravel structure

- `backend/app/Http/Controllers/` — authentication, student enrollment, registrar, and dashboard actions
- `backend/app/Http/Middleware/` — role-based access checks
- `backend/app/Models/` — Eloquent models for users, programs, subjects, and enrollments
- `backend/app/Services/` — curriculum import logic
- `backend/database/migrations/` — PostgreSQL schema migration
- `backend/database/seeders/` — demo and curriculum seed flow
- `backend/database/sql/` — PostgreSQL schema, seed data, and source curriculum snapshot
- `backend/resources/views/` — Blade layouts and role-specific pages
- `backend/public/` — directly served CSS, fonts, and college logos
- `backend/routes/web.php` — named, session-authenticated web routes

## Included workflows

- Student account registration and sign-in
- Student enrollment applications with major and subject selection, history, and withdrawal before review
- Subject catalog and student grade records
- Registrar application review, student access, program and subject catalogs, grade encoding, and CSV reports

This is a classroom project starter, not a production-ready student information system. Review account verification, authorization, audit logging, deployment settings, and data-protection requirements before real use.
