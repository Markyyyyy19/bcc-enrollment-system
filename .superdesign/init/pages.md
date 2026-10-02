# Page dependencies
/student/enrollments: resources/views/student/enrollments/index.blade.php -> layouts/app.blade.php -> public/css/tokens.css + public/css/app.css. Controller: StudentEnrollmentController@index; paginated enrollments with program, major, subjects.
/dashboard (registrar): resources/views/dashboard/registrar.blade.php -> same shell/styles. DashboardController supplies counts and six recent enrollments.
Other authenticated views use layouts/app; auth uses layouts/auth. No nested UI imports on target pages.
