<?php

use App\Features\AuditLog\ListAuditLogs\ListAuditLogsController;
use Illuminate\Support\Facades\Route;

Route::get('audit-log', ListAuditLogsController::class)->middleware(['auth', 'can:users.manage'])->name('audit-log');
