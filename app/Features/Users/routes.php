<?php

use App\Features\Users\CreateUser\CreateUserController;
use App\Features\Users\ListUsers\ListUsersController;
use App\Features\Users\UpdateUser\UpdateUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:users.manage'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', ListUsersController::class)->name('index');
    Route::get('create', [CreateUserController::class, 'create'])->name('create');
    Route::post('/', [CreateUserController::class, 'store'])->name('store');
    Route::get('{user}/edit', [UpdateUserController::class, 'edit'])->name('edit');
    Route::put('{user}', [UpdateUserController::class, 'update'])->name('update');
});
