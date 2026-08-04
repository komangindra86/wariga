<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\ContributionController;
use App\Http\Controllers\Api\V1\DewasaController;
use App\Http\Controllers\Api\V1\SavedDewasaController;
use App\Http\Controllers\Api\V1\UserFeedbackController;
use App\Http\Controllers\LunarCalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/calendar/lunar/{date}', LunarCalendarController::class)->where('date', '\d{4}-\d{2}-\d{2}');

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    Route::get('activities', [DewasaController::class, 'activities']);
    Route::get('calendar/today', [CalendarController::class, 'today']);
    Route::get('calendar/upcoming', [CalendarController::class, 'upcoming']);
    Route::get('calendar/month', [CalendarController::class, 'month']);
    Route::get('calendar/{date}', [CalendarController::class, 'show'])->where('date', '\d{4}-\d{2}-\d{2}');
    Route::get('dewasa/search', [DewasaController::class, 'search']);
    Route::get('dewasa/today', [DewasaController::class, 'today']);
    Route::get('dewasa/{activity}/{date}', [DewasaController::class, 'show'])->where('date', '\d{4}-\d{2}-\d{2}');
    Route::get('shared/{token}', [SavedDewasaController::class, 'shared']);
    Route::post('contributions', [ContributionController::class, 'store'])->middleware('throttle:5,1');

    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:authentication');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:authentication');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:password-reset');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:password-reset');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::delete('user/account', [AuthController::class, 'destroy']);
        Route::get('user/saved-dewasa', [SavedDewasaController::class, 'index']);
        Route::post('user/saved-dewasa', [SavedDewasaController::class, 'store']);
        Route::get('user/saved-dewasa/{id}', [SavedDewasaController::class, 'show']);
        Route::delete('user/saved-dewasa/{id}', [SavedDewasaController::class, 'destroy']);
        Route::post('dewasa/share', [SavedDewasaController::class, 'share']);
        Route::post('dewasa/feedback', [UserFeedbackController::class, 'store'])
            ->middleware('throttle:10,1');
    });
});
