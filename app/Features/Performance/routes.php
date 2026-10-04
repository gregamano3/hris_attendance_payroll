<?php

use App\Features\Performance\ManageCycles\CyclesController;
use App\Features\Performance\Reviews\ReviewsController;
use App\Features\Performance\Training\TrainingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('performance')->name('performance.')->group(function () {
    // Access checked inside: the reviewed employee, their reviewer or HR.
    Route::get('reviews', [ReviewsController::class, 'index'])->name('reviews.index');
    Route::get('reviews/{review}', [ReviewsController::class, 'show'])->name('reviews.show');
    Route::put('reviews/{review}/self', [ReviewsController::class, 'selfAssessment'])->name('reviews.self');
    Route::put('reviews/{review}/complete', [ReviewsController::class, 'complete'])->name('reviews.complete');
    Route::patch('reviews/{review}/acknowledge', [ReviewsController::class, 'acknowledge'])->name('reviews.acknowledge');
    Route::get('trainings/{training}/certificate', [TrainingController::class, 'certificate'])->name('trainings.certificate');

    Route::middleware('can:performance.manage')->group(function () {
        Route::get('cycles', [CyclesController::class, 'index'])->name('cycles.index');
        Route::post('cycles', [CyclesController::class, 'store'])->name('cycles.store');
        Route::get('cycles/{cycle}', [CyclesController::class, 'show'])->name('cycles.show');
        Route::patch('cycles/{cycle}/close', [CyclesController::class, 'close'])->name('cycles.close');
        Route::get('trainings', [TrainingController::class, 'index'])->name('trainings.index');
        Route::post('trainings', [TrainingController::class, 'store'])->name('trainings.store');
        Route::delete('trainings/{training}', [TrainingController::class, 'destroy'])->name('trainings.destroy');
    });
});
