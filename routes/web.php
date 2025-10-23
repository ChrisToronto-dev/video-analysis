<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\YouTubeController;

Route::get('/', [YouTubeController::class, 'index'])->name('youtube.index');
Route::match(['GET', 'POST'], '/search', [YouTubeController::class, 'search'])->name('youtube.search');
