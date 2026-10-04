<?php

use App\Features\Recruitment\ManageApplicants\ApplicantsController;
use App\Features\Recruitment\ManageApplicants\HireApplicantController;
use App\Features\Recruitment\ManageOpenings\OpeningsController;
use App\Features\Recruitment\Onboarding\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:recruitment.manage'])->prefix('recruitment')->name('recruitment.')->group(function () {
    Route::resource('openings', OpeningsController::class)->except('destroy');
    Route::patch('openings/{opening}/toggle', [OpeningsController::class, 'toggle'])->name('openings.toggle');

    Route::post('openings/{opening}/applicants', [ApplicantsController::class, 'store'])->name('applicants.store');
    Route::get('applicants/{applicant}', [ApplicantsController::class, 'show'])->name('applicants.show');
    Route::patch('applicants/{applicant}/stage', [ApplicantsController::class, 'move'])->name('applicants.move');
    Route::post('applicants/{applicant}/notes', [ApplicantsController::class, 'note'])->name('applicants.note');
    Route::get('applicants/{applicant}/resume', [ApplicantsController::class, 'resume'])->name('applicants.resume');
    Route::post('applicants/{applicant}/hire', HireApplicantController::class)->name('applicants.hire');

    Route::get('onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('onboarding/templates', [OnboardingController::class, 'storeTemplate'])->name('onboarding.templates.store');
    Route::get('onboarding/{employee}', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding/{employee}', [OnboardingController::class, 'start'])->name('onboarding.start');
    Route::patch('onboarding/tasks/{task}', [OnboardingController::class, 'toggle'])->name('onboarding.toggle');
});
