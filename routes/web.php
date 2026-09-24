<?php

use App\Http\Controllers\AttendanceImportController;
use App\Http\Controllers\CarryOverTaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');

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

// The tools run entirely in the browser (resources/js/tools), so they are plain views.
Route::prefix('tools')->name('tools.')->group(function (): void {
    Route::view('/', 'tools.index')->name('index');
    Route::view('/json', 'tools.json')->name('json');
    Route::view('/timestamp', 'tools.timestamp')->name('timestamp');
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
