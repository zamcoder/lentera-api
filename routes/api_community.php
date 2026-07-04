<?php

use App\Http\Controllers\Api\V1\Community\CircleController;
use App\Http\Controllers\Api\V1\Community\PostController;
use App\Http\Controllers\Api\V1\Community\PromptController;
use App\Http\Controllers\Api\V1\Community\StrengthController;
use Illuminate\Support\Facades\Route;

/*
| KOMUNITAS (§7-8) — Feed & Post, reaksi, hide, Lingkaran. Bidang B plaintext,
| dimoderasi. Butuh JWT. Di-mount di bawah /api/v1. Prompt/Strength (§9) menyusul.
*/
Route::middleware('auth:api')->group(function () {
    // Feed & Post (§7).
    Route::prefix('community')->group(function () {
        Route::get('/feed', [PostController::class, 'feed']);
        Route::get('/posts/{post}', [PostController::class, 'show']);
        Route::post('/posts', [PostController::class, 'store'])->middleware('throttle:community-post');

        Route::post('/posts/{post}/react', [PostController::class, 'react'])->middleware('throttle:community-react');
        Route::delete('/posts/{post}/react', [PostController::class, 'unreact'])->middleware('throttle:community-react');
        Route::post('/posts/{post}/hide', [PostController::class, 'hide']);
    });

    // Lingkaran (§8).
    Route::get('/circles', [CircleController::class, 'index']);
    Route::get('/circles/{circle}', [CircleController::class, 'show']);
    Route::get('/circles/{circle}/feed', [CircleController::class, 'feed']);
    Route::post('/circles/{circle}/join', [CircleController::class, 'join']);
    Route::delete('/circles/{circle}/join', [CircleController::class, 'leave']);

    // Prompt bersama (§9).
    Route::get('/prompts/today', [PromptController::class, 'today']);
    Route::get('/prompts/today/answers', [PromptController::class, 'answers']);
    Route::post('/prompts/today/answers', [PromptController::class, 'storeAnswer'])->middleware('throttle:community-post');

    // Kirim kekuatan (§9) — instan.
    Route::get('/strength/queue', [StrengthController::class, 'queue']);
    Route::post('/strength/{post}/send', [StrengthController::class, 'send'])->middleware('throttle:community-post');
});
