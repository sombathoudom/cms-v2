<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\UserController;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:public-content-api')->group(function () {
    Route::get('/posts', [PostController::class, 'index'])->name('api.v1.posts.index');
    Route::get('/posts/{slug}', [PostController::class, 'show'])->name('api.v1.posts.show');
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('api.v1.pages.show');
});

Route::prefix('v1')->middleware(['auth:web'])->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('can:viewAny,'.AuditLog::class)
        ->name('api.v1.audit-logs.index');

    Route::apiResource('users', UserController::class)
        ->parameters(['users' => 'user'])
        ->names([
            'index' => 'api.v1.users.index',
            'store' => 'api.v1.users.store',
            'show' => 'api.v1.users.show',
            'update' => 'api.v1.users.update',
            'destroy' => 'api.v1.users.destroy',
        ]);

    Route::post('/users/{user}/restore', [UserController::class, 'restore'])
        ->name('api.v1.users.restore');
});

Route::get('/health', \App\Http\Controllers\HealthController::class);
