<?php

use App\Http\Controllers\Community\CircleController;
use App\Http\Controllers\Community\PostController;
use App\Http\Controllers\Community\PromptController;
use Illuminate\Support\Facades\Route;

/*
| COMMUNITY (§A4) — feed, posts, reactions, circles, prompt. Butuh token app.
| Rate limit per akun untuk posting & reaksi (§03).
*/
Route::middleware('auth:sanctum')->group(function () {
    // Feed & prompt (baca).
    Route::get('/feed', [PostController::class, 'feed']);
    Route::get('/prompt/today', [PromptController::class, 'today']);

    // Posting (rate-limited).
    Route::post('/posts', [PostController::class, 'store'])->middleware('throttle:community-post');

    // Reaksi hangat (rate-limited), tanpa komentar.
    Route::post('/posts/{post}/react', [PostController::class, 'react'])->middleware('throttle:community-react');
    Route::delete('/posts/{post}/react', [PostController::class, 'unreact'])->middleware('throttle:community-react');

    // Lingkaran.
    Route::get('/circles', [CircleController::class, 'index']);
    Route::get('/circles/{circle}', [CircleController::class, 'show']);
    Route::post('/circles/{circle}/join', [CircleController::class, 'join']);
    Route::delete('/circles/{circle}/leave', [CircleController::class, 'leave']);
    Route::get('/circles/{circle}/feed', [CircleController::class, 'feed']);
});
