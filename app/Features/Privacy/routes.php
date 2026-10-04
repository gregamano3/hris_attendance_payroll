<?php

use App\Features\Privacy\AcknowledgeNotice\AcknowledgeNoticeController;
use App\Features\Privacy\DataSubjectRequests\DataSubjectRequestsController;
use App\Features\Privacy\ExportMyData\ExportMyDataController;
use App\Features\Privacy\ManageNotices\NoticesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('privacy')->name('privacy.')->group(function () {
    Route::get('notice', [AcknowledgeNoticeController::class, 'show'])->name('notice');
    Route::post('notice', [AcknowledgeNoticeController::class, 'store'])->name('notice.acknowledge');

    Route::get('/', [DataSubjectRequestsController::class, 'mine'])->name('mine');
    Route::post('requests', [DataSubjectRequestsController::class, 'store'])->name('requests.store');
    Route::get('export', ExportMyDataController::class)->middleware('throttle:5,1')->name('export');

    Route::middleware('can:users.manage')->group(function () {
        Route::get('requests', [DataSubjectRequestsController::class, 'index'])->name('requests.index');
        Route::patch('requests/{dataSubjectRequest}', [DataSubjectRequestsController::class, 'update'])->name('requests.update');
        Route::get('notices', [NoticesController::class, 'index'])->name('notices.index');
        Route::post('notices', [NoticesController::class, 'store'])->name('notices.store');
        Route::patch('notices/{notice}/publish', [NoticesController::class, 'publish'])->name('notices.publish');
    });
});
