<?php

use App\Http\Controllers\Admin\AdminModuleController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpertSystemController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PublicPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/privacy-policy', [PublicPageController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/terms', [PublicPageController::class, 'terms'])->name('terms');
Route::get('/account-deletion', [PublicPageController::class, 'accountDeletion'])->name('account-deletion');

Route::get('/health', HealthController::class)->name('health');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:authentication')->name('login.store');
    });
    Route::middleware(['auth', 'admin.role:viewer,admin_data,validator_wariga'])->group(function (): void {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/master/{type}', [AdminModuleController::class, 'master'])->name('master');
        Route::get('/lunar', [AdminModuleController::class, 'lunar'])->name('lunar');
        Route::post('/lunar/sync', [AdminModuleController::class, 'syncLunar'])->name('lunar.sync');
        Route::post('/lunar/validate', [AdminModuleController::class, 'validateLunar'])->name('lunar.validate');
        Route::post('/lunar/import', [AdminModuleController::class, 'importLunar'])->name('lunar.import');
        Route::post('/lunar/{lunar}/transition', [AdminModuleController::class, 'transitionLunar'])->name('lunar.transition');
        Route::get('/audit-log', [AdminModuleController::class, 'audits'])->name('audits');
        Route::get('/activities', [AdminModuleController::class, 'activities'])->name('activities');
        Route::get('/contributions', [AdminModuleController::class, 'contributions'])->name('contributions');
        Route::post('/contributions/{contribution}/transition', [AdminModuleController::class, 'transitionContribution'])->name('contributions.transition');
        Route::post('/activities', [AdminModuleController::class, 'storeActivity'])->name('activities.store');
        Route::put('/activities/{activity}', [AdminModuleController::class, 'updateActivity'])->name('activities.update');
        Route::get('/import', [AdminModuleController::class, 'import'])->name('import');
        Route::get('/settings', [AdminModuleController::class, 'settings'])->name('settings');
        Route::put('/settings', [AdminModuleController::class, 'updateSettings'])->name('settings.update');
        Route::get('/expert-system', [ExpertSystemController::class, 'index'])->name('expert.index');
        Route::get('/expert-system/knowledge', [ExpertSystemController::class, 'knowledge'])->name('expert.knowledge');
        Route::get('/expert-system/simulator', [ExpertSystemController::class, 'simulator'])->name('expert.simulator');
        Route::post('/expert-system/simulator', [ExpertSystemController::class, 'simulate'])->name('expert.simulate');
        Route::post('/expert-system/mappings', [ExpertSystemController::class, 'storeMapping'])->name('expert.mappings.store');
        Route::delete('/expert-system/mappings/{mapping}', [ExpertSystemController::class, 'destroyMapping'])->name('expert.mappings.destroy');
        Route::post('/expert-system/cases', [ExpertSystemController::class, 'storeCase'])->name('expert.cases.store');
        Route::post('/expert-system/proposals/generate', [ExpertSystemController::class, 'generate'])->name('expert.proposals.generate');
        Route::post('/expert-system/proposals/{proposal}/transition', [ExpertSystemController::class, 'transitionProposal'])->name('expert.proposals.transition');
    });
});
