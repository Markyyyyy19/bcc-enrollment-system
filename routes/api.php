<?php

use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\GradeController;
use App\Http\Controllers\Api\V1\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware(['web', 'auth', 'active'])->group(function (): void {
    Route::get('/me', [StudentController::class, 'me'])->name('me');

    Route::get('/programs', [CatalogController::class, 'programs'])->name('programs.index');
    Route::get('/programs/{program}/majors', [CatalogController::class, 'majors'])->name('programs.majors');
    Route::post('/programs', [CatalogController::class, 'storeProgram'])->middleware('role:registrar')->name('programs.store');
    Route::patch('/programs/{program}', [CatalogController::class, 'updateProgram'])->middleware('role:registrar')->name('programs.update');
    Route::patch('/programs/{program}/toggle', [CatalogController::class, 'toggleProgram'])->middleware('role:registrar')->name('programs.toggle');

    Route::get('/subjects', [CatalogController::class, 'subjects'])->name('subjects.index');
    Route::get('/subjects/loaded', [CatalogController::class, 'schedules'])->name('subjects.loaded');
    Route::get('/schedules', [CatalogController::class, 'schedules'])->name('schedules.index');
    Route::get('/instructors/loaded-subjects', [CatalogController::class, 'instructorSubjects'])->name('instructors.subjects');
    Route::get('/instructors/loaded-subjects/by-class', [CatalogController::class, 'classSubjects'])->name('instructors.subjects.class');
    Route::get('/instructors/{instructor}/loaded-subjects', [CatalogController::class, 'instructorSubjects'])->name('instructors.subjects.show');
    Route::post('/subjects', [CatalogController::class, 'storeSubject'])->middleware('role:registrar')->name('subjects.store');
    Route::post('/subjects/bulk', [CatalogController::class, 'bulkSubjects'])->middleware('role:registrar')->name('subjects.bulk');
    Route::patch('/subjects/{subject}', [CatalogController::class, 'updateSubject'])->middleware('role:registrar')->name('subjects.update');
    Route::patch('/subjects/{subject}/toggle', [CatalogController::class, 'toggleSubject'])->middleware('role:registrar')->name('subjects.toggle');

    Route::middleware('role:registrar')->group(function (): void {
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::get('/students/lookup/{studentNumber}', [StudentController::class, 'lookup'])->name('students.lookup');
        Route::get('/students/{student}', [StudentController::class, 'show'])->whereNumber('student')->name('students.show');
        Route::patch('/students/{student}', [StudentController::class, 'update'])->whereNumber('student')->name('students.update');
        Route::patch('/students/{student}/access', [StudentController::class, 'toggleAccess'])->whereNumber('student')->name('students.access');
        Route::get('/users', [StudentController::class, 'users'])->name('users.index');

        Route::patch('/enrollments/{enrollment}/status', [EnrollmentController::class, 'review'])->whereNumber('enrollment')->name('enrollments.status');
        Route::post('/enrollments/{enrollment}/forward', [EnrollmentController::class, 'forward'])->whereNumber('enrollment')->name('enrollments.forward');
        Route::post('/enrollments/{enrollment}/return', [EnrollmentController::class, 'returnToStudent'])->whereNumber('enrollment')->name('enrollments.return');
        Route::patch('/grades/{enrollment}/{subject}', [GradeController::class, 'update'])->whereNumber('enrollment')->whereNumber('subject')->name('grades.update');
    });

    Route::get('/students/{student}/enrollments', [StudentController::class, 'enrollments'])->whereNumber('student')->name('students.enrollments');
    Route::get('/students/{student}/grades', [GradeController::class, 'forStudent'])->whereNumber('student')->name('students.grades');

    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('/enrollments/count', [EnrollmentController::class, 'count'])->name('enrollments.count');
    Route::get('/enrollments/by-student/{student}/subject', [EnrollmentController::class, 'byStudentSubject'])->whereNumber('student')->name('enrollments.student-subject');
    Route::get('/enrollments/{enrollment}', [EnrollmentController::class, 'show'])->whereNumber('enrollment')->name('enrollments.show');

    Route::middleware('role:student')->group(function (): void {
        Route::post('/enrollments', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::post('/enrollments/{enrollment}/subjects', [EnrollmentController::class, 'addSubjects'])->whereNumber('enrollment')->name('enrollments.subjects.add');
        Route::patch('/enrollments/{enrollment}/subjects', [EnrollmentController::class, 'updateSubjects'])->whereNumber('enrollment')->name('enrollments.subjects.update');
        Route::delete('/enrollments/{enrollment}/subjects/{subject}', [EnrollmentController::class, 'deleteSubject'])->whereNumber('enrollment')->whereNumber('subject')->name('enrollments.subjects.delete');
        Route::post('/enrollments/{enrollment}/subjects/{subject}/drop', [EnrollmentController::class, 'deleteSubject'])->whereNumber('enrollment')->whereNumber('subject')->name('enrollments.subjects.drop');
        Route::post('/enrollments/{enrollment}/submit', [EnrollmentController::class, 'submit'])->whereNumber('enrollment')->name('enrollments.submit');
        Route::patch('/enrollments/{enrollment}/withdraw', [EnrollmentController::class, 'withdraw'])->whereNumber('enrollment')->name('enrollments.withdraw');
    });

    Route::get('/grades', [GradeController::class, 'index'])->name('grades.index');
});
