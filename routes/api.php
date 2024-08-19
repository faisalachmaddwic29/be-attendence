<?php

use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ScheduleController;
use App\Http\Controllers\API\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/login', AuthController::class);

Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::group(['prefix' => 'attendance'], function () {
        Route::get('/today', [AttendanceController::class, 'getAttendanceToday']);
        Route::get('/month-year/{month}/{year}', [AttendanceController::class, 'getAttendanceMonthYear']);
        Route::post('/store', [AttendanceController::class, 'store']);
    });

    Route::group(['prefix' => 'schedule'], function () {
        Route::get('/list', [ScheduleController::class, 'getList']);
        Route::post('/banned', [ScheduleController::class, 'banned']);
    });

    Route::group(['prefix' => 'user'], function () {
        Route::get('/image', UserController::class);
    });
});
