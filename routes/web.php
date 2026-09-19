<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\OnboardingDraftController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecruitmentController;
use App\Http\Controllers\UserAccessController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('home');
Route::get('/apply', [RecruitmentController::class, 'apply'])->name('apply');
Route::post('/apply', [RecruitmentController::class, 'store'])->middleware('throttle:10,1')->name('apply.store');
Route::get('/apply/draft', [OnboardingDraftController::class, 'show'])->name('apply.draft.show');
Route::post('/apply/draft', [OnboardingDraftController::class, 'store'])->middleware('throttle:60,1')->name('apply.draft.store');
Route::delete('/apply/draft', [OnboardingDraftController::class, 'destroy'])->name('apply.draft.destroy');
Route::post('/cookie-consent', [CookieConsentController::class, 'store'])->middleware('throttle:20,1')->name('cookie-consent.store');

Route::get('/dashboard', function () {
    return to_route('admin.recruitment');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::middleware('permission:dashboard.view')->group(function () {
            Route::get('/', [RecruitmentController::class, 'dashboard'])->name('dashboard');
            Route::get('/recruitment', [RecruitmentController::class, 'dashboard'])->name('recruitment');
        });
        Route::middleware('permission:leads.view')->group(function () {
            Route::get('/leads', [RecruitmentController::class, 'leads'])->name('leads');
            Route::get('/leads/{lead}', [RecruitmentController::class, 'lead'])->name('leads.show');
        });
        Route::post('/leads/{lead}/convert', [RecruitmentController::class, 'convert'])->middleware('permission:candidates.convert')->name('leads.convert');
        Route::middleware('permission:candidates.view')->group(function () {
            Route::get('/candidates', [RecruitmentController::class, 'candidates'])->name('candidates');
            Route::get('/candidates/{candidate}', [RecruitmentController::class, 'candidate'])->name('candidates.show');
        });
        Route::middleware('permission:roles.manage')->group(function () {
            Route::get('/access', [AccessControlController::class, 'index'])->name('access.index');
            Route::get('/access/create', [AccessControlController::class, 'create'])->name('access.create');
            Route::post('/access', [AccessControlController::class, 'store'])->name('access.store');
            Route::get('/access/{role}/edit', [AccessControlController::class, 'edit'])->name('access.edit');
            Route::put('/access/{role}', [AccessControlController::class, 'update'])->name('access.update');
            Route::delete('/access/{role}', [AccessControlController::class, 'destroy'])->name('access.destroy');
        });
        Route::middleware('permission:users.manage')->group(function () {
            Route::get('/users', [UserAccessController::class, 'index'])->name('users.index');
            Route::get('/users/{user}/edit', [UserAccessController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserAccessController::class, 'update'])->name('users.update');
        });
    });
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
