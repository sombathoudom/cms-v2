<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', \App\Http\Controllers\HealthController::class);
