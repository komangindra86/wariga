<?php

use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/privacy-policy', [PublicPageController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/terms', [PublicPageController::class, 'terms'])->name('terms');
Route::get('/account-deletion', [PublicPageController::class, 'accountDeletion'])->name('account-deletion');

Route::get('/health', HealthController::class)->name('health');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AdminPageController::class, 'login'])->name('login');
    Route::get('/dashboard', [AdminPageController::class, 'dashboard'])->name('dashboard');
});
