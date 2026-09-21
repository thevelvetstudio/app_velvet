<?php

use App\Http\Controllers\AccessControlController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CookieConsentController;
use App\Http\Controllers\OnboardingDraftController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecruitmentController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\UserAccessController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('home');
Route::get('/apply', [RecruitmentController::class, 'apply'])->name('apply');
Route::get('/politica-de-privacidad', fn () => Inertia::render('Public/PrivacyPolicy'))->name('privacy');
Route::get('/terminos-y-condiciones', fn () => Inertia::render('Public/TermsAndConditions'))->name('terms');
Route::post('/apply', [RecruitmentController::class, 'store'])->middleware('throttle:10,1')->name('apply.store');
Route::get('/apply/draft', [OnboardingDraftController::class, 'show'])->name('apply.draft.show');
Route::post('/apply/draft', [OnboardingDraftController::class, 'store'])->middleware('throttle:60,1')->name('apply.draft.store');
Route::delete('/apply/draft', [OnboardingDraftController::class, 'destroy'])->name('apply.draft.destroy');
Route::get('/prequalification/{candidate}/{token}', [RecruitmentController::class, 'prequalification'])->middleware('signed')->name('prequalification.show');
Route::get('/prequalification/{candidate}/{token}/identity-callback', [RecruitmentController::class, 'diditCallback'])->name('prequalification.identity.callback');
Route::post('/prequalification/{candidate}/{token}', [RecruitmentController::class, 'storePrequalification'])->middleware(['signed', 'throttle:10,1'])->name('prequalification.store');
Route::post('/prequalification/{candidate}/{token}/identity-session', [RecruitmentController::class, 'createIdentityVerificationSession'])->middleware(['signed', 'throttle:5,1'])->name('prequalification.identity.session');
Route::post('/webhooks/didit', [RecruitmentController::class, 'diditWebhook'])->name('didit.webhook');
Route::get('/entrevista/{interview}/{token}', [InterviewController::class, 'booking'])->middleware('signed')->name('interview.booking.show');
Route::post('/entrevista/{interview}/{token}', [InterviewController::class, 'book'])->middleware(['signed', 'throttle:10,1'])->name('interview.booking.book');
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
        Route::get('/processes', [RecruitmentController::class, 'processes'])->middleware('permission:onboarding.view')->name('processes');
        Route::middleware('permission:leads.view')->group(function () {
            Route::get('/leads', [RecruitmentController::class, 'leads'])->name('leads');
            Route::get('/leads/{lead}', [RecruitmentController::class, 'lead'])->name('leads.show');
        });
        Route::put('/leads/{lead}', [RecruitmentController::class, 'updateLead'])->middleware('permission:leads.manage')->name('leads.update');
        Route::post('/leads/{lead}/discard', [RecruitmentController::class, 'discardLead'])->middleware('permission:leads.manage')->name('leads.discard');
        Route::post('/leads/{lead}/convert', [RecruitmentController::class, 'convert'])->middleware('permission:candidates.convert')->name('leads.convert');
        Route::middleware('permission:candidates.view')->group(function () {
            Route::get('/candidates', [RecruitmentController::class, 'candidates'])->name('candidates');
            Route::get('/candidates/{candidate}', [RecruitmentController::class, 'candidate'])->name('candidates.show');
        });
        Route::put('/candidates/{candidate}', [RecruitmentController::class, 'updateCandidate'])->middleware('permission:candidates.update')->name('candidates.update');
        Route::post('/candidates/{candidate}/discard', [RecruitmentController::class, 'discardCandidate'])->middleware('permission:candidates.reject')->name('candidates.discard');
        Route::patch('/candidates/{candidate}/status', [RecruitmentController::class, 'updateCandidateStatus'])->middleware('permission:candidates.change_status')->name('candidates.status.update');
        Route::post('/candidates/{candidate}/prequalification', [RecruitmentController::class, 'sendPrequalification'])->middleware('permission:candidates.request_documents')->name('candidates.prequalification.send');
        Route::middleware('permission:interviews.view')->group(function () {
            Route::get('/interviews', [InterviewController::class, 'index'])->name('interviews.index');
        });
        Route::middleware('permission:documents.verify')->group(function () {
            Route::get('/validations', [RecruitmentController::class, 'validations'])->name('validations');
        });
        Route::middleware('permission:calendar.view')->group(function () {
            Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
        });
        Route::middleware('permission:calendar.manage')->group(function () {
            Route::post('/calendar/events', [CalendarController::class, 'store'])->name('calendar.events.store');
            Route::patch('/calendar/events/{calendarEvent}/complete', [CalendarController::class, 'complete'])->name('calendar.events.complete');
            Route::patch('/calendar/events/{calendarEvent}', [CalendarController::class, 'update'])->name('calendar.events.update');
            Route::delete('/calendar/events/{calendarEvent}', [CalendarController::class, 'destroy'])->name('calendar.events.destroy');
        });
        Route::post('/interviews/slots', [InterviewController::class, 'generateSlots'])->middleware('permission:candidates.schedule_interview')->name('interviews.slots.generate');
        Route::post('/candidates/{candidate}/interview-invitation', [InterviewController::class, 'invite'])->middleware('permission:candidates.schedule_interview')->name('candidates.interview.invite');
        Route::patch('/interviews/{interview}/status', [InterviewController::class, 'updateStatus'])->middleware('permission:candidates.schedule_interview')->name('interviews.status.update');
        Route::post('/interviews/{interview}/reschedule', [InterviewController::class, 'reschedule'])->middleware('permission:candidates.schedule_interview')->name('interviews.reschedule');
        Route::middleware('permission:roles.manage')->group(function () {
            Route::get('/access', [AccessControlController::class, 'index'])->name('access.index');
            Route::get('/access/create', [AccessControlController::class, 'create'])->name('access.create');
            Route::post('/access', [AccessControlController::class, 'store'])->name('access.store');
            Route::get('/access/{role}/edit', [AccessControlController::class, 'edit'])->name('access.edit');
            Route::put('/access/{role}', [AccessControlController::class, 'update'])->name('access.update');
            Route::delete('/access/{role}', [AccessControlController::class, 'destroy'])->name('access.destroy');
        });
        Route::get('/users', [UserAccessController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::get('/users/create', [UserAccessController::class, 'create'])->middleware('permission:users.create')->name('users.create');
        Route::post('/users', [UserAccessController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::get('/users/{user}/edit', [UserAccessController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
        Route::put('/users/{user}', [UserAccessController::class, 'update'])->middleware('permission:users.update')->name('users.update');
        Route::delete('/users/{user}', [UserAccessController::class, 'destroy'])->middleware('permission:users.disable')->name('users.destroy');
    });
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
