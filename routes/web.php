<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\SocialAuthController;
use App\Livewire\BookCatalog;
use Illuminate\Support\Facades\Route;

Route::get('/', [ContentController::class, 'index'])->name('home');
Route::get('/catalog', BookCatalog::class)->name('catalog');
Route::get('/login', fn () => redirect()->route('home')->with('openDialog', 'login-dialog'));
Route::get('/register', fn () => redirect()->route('home')->with('openDialog', 'register-dialog'));
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'microsoft', 'tiktok', 'linkedin'])
    ->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'microsoft', 'tiktok', 'linkedin'])
    ->name('social.callback');
Route::get('/auth/complete-profile', [SocialAuthController::class, 'completeProfile'])
    ->name('social.complete-profile');
Route::post('/auth/complete-profile', [SocialAuthController::class, 'storeProfile'])
    ->name('social.complete-profile.store');

Route::middleware('guest')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::get('/api/content/{content}/comments', [CommunityController::class, 'comments'])->name('comments.index');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/auth/{provider}/connect', [SocialAuthController::class, 'connect'])
        ->whereIn('provider', ['google', 'microsoft', 'tiktok', 'linkedin'])
        ->name('social.connect');
    Route::post('/content', [ContentController::class, 'store'])->name('content.store');
    Route::post('/content/{content}/publish', [ContentController::class, 'publish'])->name('content.publish');
    Route::post('/content/{content}/bookmark', [ContentController::class, 'bookmark'])->name('content.bookmark');
    Route::post('/content/{content}/reaction', [ContentController::class, 'react'])->name('content.react');
    Route::post('/api/content/{content}/comments', [CommunityController::class, 'storeComment'])->name('comments.store');
    Route::get('/api/chat/{user}', [CommunityController::class, 'messages'])->name('chat.index');
    Route::post('/api/chat/{user}', [CommunityController::class, 'storeMessage'])->name('chat.store');
});
