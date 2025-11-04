<?php

use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:public-content-api')->group(function () {
    Route::get('/posts', [PostController::class, 'index'])->name('api.v1.posts.index');
    Route::get('/posts/{slug}', [PostController::class, 'show'])->name('api.v1.posts.show');
    Route::get('/pages/{slug}', [PageController::class, 'show'])->name('api.v1.pages.show');
});

use Illuminate\Support\Facades\Route;

Route::get('/health', \App\Http\Controllers\HealthController::class);
