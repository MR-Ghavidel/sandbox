<?php

use App\Http\Controllers\AlarmController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceImportController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\BaleLinkController;
use App\Http\Controllers\BaleWebhookController;
use App\Http\Controllers\CarryOverTaskController;
use App\Http\Controllers\ClassSessionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeployRunnerController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScheduleSettingsController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SiteImportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskOrderController;
use Illuminate\Support\Facades\Route;

// Bale calls this endpoint; it authenticates with the secret in the URL, not a CSRF token.
Route::post('/bale/webhook/{secret?}', BaleWebhookController::class)->name('bale.webhook');

// For shared hosts without terminal access; disabled until DEPLOY_RUNNER_TOKEN is set.
Route::match(['get', 'post'], '/deploy/artisan/{command}', DeployRunnerController::class)
    ->where('command', '[a-z-]+')
    ->middleware('throttle:20,1')
    ->name('deploy.artisan');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

// The tools run entirely in the browser (resources/js/tools), so they are public plain views.
Route::prefix('tools')->name('tools.')->group(function (): void {
    Route::view('/', 'tools.index')->name('index');
    Route::view('/json', 'tools.json')->name('json');
    Route::view('/timestamp', 'tools.timestamp')->name('timestamp');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('home');

    Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');

    Route::controller(ScheduleSettingsController::class)
        ->prefix('settings')
        ->name('settings.')
        ->group(function (): void {
            Route::get('/', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
            Route::patch('/current-week', 'updateCurrentWeek')->name('current-week');
            Route::post('/bale-code', [BaleLinkController::class, 'store'])->name('bale-code');
            Route::delete('/bale', [BaleLinkController::class, 'destroy'])->name('bale.destroy');
        });

    Route::controller(CourseController::class)
        ->prefix('courses')
        ->name('courses.')
        ->where(['course' => '[0-9]+'])
        ->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{course}', 'show')->name('show');
            Route::get('/{course}/edit', 'edit')->name('edit');
            Route::put('/{course}', 'update')->name('update');
            Route::delete('/{course}', 'destroy')->name('destroy');
        });

    Route::post('/courses/{course}/links', [CourseLinkController::class, 'store'])
        ->whereNumber('course')->name('courses.links.store');
    Route::put('/courses/{course}/links/{link}', [CourseLinkController::class, 'update'])
        ->whereNumber(['course', 'link'])->name('courses.links.update');
    Route::delete('/courses/{course}/links/{link}', [CourseLinkController::class, 'destroy'])
        ->whereNumber(['course', 'link'])->name('courses.links.destroy');

    Route::post('/courses/{course}/sessions', [ClassSessionController::class, 'store'])
        ->whereNumber('course')->name('courses.sessions.store');
    Route::put('/courses/{course}/sessions/{session}', [ClassSessionController::class, 'update'])
        ->whereNumber(['course', 'session'])->name('courses.sessions.update');
    Route::delete('/courses/{course}/sessions/{session}', [ClassSessionController::class, 'destroy'])
        ->whereNumber(['course', 'session'])->name('courses.sessions.destroy');

    Route::controller(AlarmController::class)
        ->prefix('alarms')
        ->name('alarms.')
        ->where(['alarm' => '[0-9]+'])
        ->group(function (): void {
            Route::post('/', 'store')->name('store');
            Route::patch('/{alarm}', 'update')->name('update');
            Route::delete('/{alarm}', 'destroy')->name('destroy');
        });

    Route::controller(AssignmentController::class)
        ->prefix('assignments')
        ->name('assignments.')
        ->where(['assignment' => '[0-9]+'])
        ->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('/{assignment}', 'update')->name('update');
            Route::patch('/{assignment}/done', 'toggleDone')->name('done');
            Route::delete('/{assignment}', 'destroy')->name('destroy');
        });

    Route::patch('/tasks/order', TaskOrderController::class)->name('tasks.order');
    Route::post('/tasks/carry-over', CarryOverTaskController::class)->name('tasks.carry-over');
    Route::resource('tasks', TaskController::class)->only(['index', 'store', 'update', 'destroy'])
        ->where(['task' => '[0-9]+']);

    Route::controller(PayrollController::class)
        ->prefix('payroll')
        ->name('payroll.')
        ->where(['year' => '1[3-4][0-9]{2}', 'month' => '[1-9]|1[0-2]', 'date' => '[0-9]{4}-[0-9]{2}-[0-9]{2}'])
        ->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('/{year}/{month}', 'show')->name('show');
            Route::put('/{year}/{month}/settings', 'updateSettings')->name('settings.update');
            Route::put('/{year}/{month}/days/{date}', 'updateDay')->name('days.update');
        });

    Route::post('/notes/preview', [NoteController::class, 'preview'])->name('notes.preview');
    Route::patch('/notes/{note}/pin', [NoteController::class, 'pin'])->name('notes.pin')->where('note', '[0-9]+');
    Route::resource('notes', NoteController::class)->where(['note' => '[0-9]+']);

    Route::controller(SiteController::class)
        ->prefix('sites')
        ->name('sites.')
        ->where(['site' => '[0-9]+'])
        ->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{site}/edit', 'edit')->name('edit');
            Route::put('/{site}', 'update')->name('update');
            Route::patch('/{site}/pin', 'pin')->name('pin');
            Route::get('/{site}/open', 'open')->name('open');
            Route::get('/{site}/icon', 'icon')->name('icon');
            Route::delete('/{site}', 'destroy')->name('destroy');
        });

    // "store" is called by the browser extension, so it is excluded from CSRF protection in bootstrap/app.php.
    Route::controller(SiteImportController::class)
        ->prefix('site-imports')
        ->name('site-imports.')
        ->where(['import' => '[0-9]+'])
        ->group(function (): void {
            Route::post('/', 'store')->name('store');
            Route::get('/{import}', 'show')->name('show');
            Route::post('/{import}/apply', 'apply')->name('apply');
        });

    // "store" is called by the Bizagi Chrome extension, so it is excluded from CSRF protection in bootstrap/app.php.
    Route::controller(AttendanceImportController::class)
        ->prefix('attendance-imports')
        ->name('attendance-imports.')
        ->where(['import' => '[0-9]+'])
        ->group(function (): void {
            Route::post('/', 'store')->name('store');
            Route::post('/excel', 'storeExcel')->name('excel');
            Route::get('/{import}', 'show')->name('show');
            Route::post('/{import}/apply', 'apply')->name('apply');
        });
});
