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
    ->where(['year' => '1[3-4][0-9]{2}', 'month' => '[1-9]|1[0-2]'])
    ->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('/{year}/{month}', 'show')->name('show');
        Route::put('/{year}/{month}/settings', 'updateSettings')->name('settings.update');
        Route::put('/{year}/{month}/days', 'updateDays')->name('days.update');
    });

// "store" is called by the Bizagi Chrome extension, so it is excluded from CSRF protection in bootstrap/app.php.
Route::controller(AttendanceImportController::class)
    ->prefix('attendance-imports')
    ->name('attendance-imports.')
    ->where(['import' => '[0-9]+'])
    ->group(function (): void {
        Route::post('/', 'store')->name('store');
        Route::get('/{import}', 'show')->name('show');
        Route::post('/{import}/apply', 'apply')->name('apply');
    });
