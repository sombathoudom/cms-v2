<?php

use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostController::class, 'home'])->name('home');
Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/archive/{year}/{month?}', [PostController::class, 'archive'])
    ->whereNumber('year')
    ->whereNumber('month')
    ->name('posts.archive');
Route::get('/posts/{slug}', [PostController::class, 'show'])->name('posts.show');
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');

Route::get('/health', \App\Http\Controllers\HealthController::class);

require __DIR__.'/auth.php';
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', \App\Http\Controllers\HealthController::class);
