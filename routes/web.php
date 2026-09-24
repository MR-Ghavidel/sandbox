<?php

use App\Http\Controllers\CarryOverTaskController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');

Route::patch('/tasks/order', TaskOrderController::class)->name('tasks.order');
Route::post('/tasks/carry-over', CarryOverTaskController::class)->name('tasks.carry-over');
Route::resource('tasks', TaskController::class)->only(['index', 'store', 'update', 'destroy'])
    ->where(['task' => '[0-9]+']);
