<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login');
Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'doLogin'])->name('auth.do_login');

Route::middleware('auth')->group(function () {
    Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

    Route::post('/meetings', [\App\Http\Controllers\MeetingController::class, 'store'])->name('meetings.store');

    Route::get('/end-impersonate', [\App\Http\Controllers\Admin\ImpersonateController::class, 'destroy'])->name('impersonate.leave');

    Route::middleware('admin.only')->prefix('/admin')->group(function () {
        Route::get('/impersonate/{user}', [\App\Http\Controllers\Admin\ImpersonateController::class, 'store'])->name('impersonate');

        Route::get('/options', [\App\Http\Controllers\Admin\OptionsController::class, 'edit'])->name('admin.options.edit');
        Route::post('/options', [\App\Http\Controllers\Admin\OptionsController::class, 'update'])->name('admin.options.update');

        Route::get('/admins', [\App\Http\Controllers\Admin\AdminController::class, 'edit'])->name('admin.admins.edit');

        Route::get('/student/{student}', [\App\Http\Controllers\Admin\StudentController::class, 'show'])->name('admin.student.show');
        Route::post('/student/{student}', [\App\Http\Controllers\Admin\StudentController::class, 'update'])->name('admin.student.update');
        Route::get('/student/{student}/delete', [\App\Http\Controllers\Admin\StudentController::class, 'confirmDestroy'])->name('admin.student.confirm_delete');
        Route::post('/student/{student}/delete', [\App\Http\Controllers\Admin\StudentController::class, 'destroy'])->name('admin.student.delete');

        Route::post('/note/{note}/update', [\App\Http\Controllers\StudentNoteController::class, 'update'])->name('admin.student.notes.update');
        Route::post('/note/{note}/delete', [\App\Http\Controllers\StudentNoteController::class, 'destroy'])->name('admin.student.notes.delete');

        Route::get('/reports/overdue/{type}', [\App\Http\Controllers\Reports\OverdueController::class, 'index'])->name('reports.overdue');
        Route::get('/reports/student/{student}', [\App\Http\Controllers\Reports\StudentController::class, 'index'])->name('reports.student');
        Route::get('/reports/supervisor/{supervisor}', [\App\Http\Controllers\Reports\SupervisorController::class, 'show'])->name('reports.supervisor');
        Route::get('/reports/supervisors', [\App\Http\Controllers\Reports\SupervisorController::class, 'index'])->name('reports.supervisors');

        Route::get('/bulk-edit-students/{type}', [\App\Http\Controllers\Admin\BulkEditStudentsController::class, 'edit'])->name('admin.bulk-edit-students.edit');
        Route::post('/bulk-edit-students/{type}', [\App\Http\Controllers\Admin\BulkEditStudentsController::class, 'update'])->name('admin.bulk-edit-students.update');

        Route::post('/import/phds', [\App\Http\Controllers\Imports\PhdsController::class, 'store'])->name('admin.import.phds.store');
        Route::get('/import/phds', [\App\Http\Controllers\Imports\PhdsController::class, 'create'])->name('admin.import.phds.create');

        Route::post('/import/projectstudents', [\App\Http\Controllers\Imports\ProjectStudentsController::class, 'store'])->name('admin.import.project-students.store');
        Route::get('/import/projectstudents', [\App\Http\Controllers\Imports\ProjectStudentsController::class, 'create'])->name('admin.import.project-students.create');

        Route::get('/export/phds', [\App\Http\Controllers\Exports\PhdsController::class, 'show'])->name('admin.export.phds');

        Route::get('/gdpr/student/{student}', [\App\Http\Controllers\Exports\GdprController::class, 'student'])->name('admin.gdpr.student.export');
        Route::get('/gdpr/staff/{user}', [\App\Http\Controllers\Exports\GdprController::class, 'staff'])->name('admin.gdpr.staff.export');

        Route::get('/activity', [\App\Http\Controllers\Admin\ActivityController::class, 'index'])->name('admin.activity.index');
    });
});
