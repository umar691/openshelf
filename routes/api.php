<?php

use App\Http\Controllers\Api\ApiTokenController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/payments/{gateway}/webhook', PaymentWebhookController::class)
        ->whereIn('gateway', ['stripe', 'easypaisa', 'jazzcash'])
        ->name('payments.webhook');
    Route::get('/books', [BookController::class, 'index'])->name('books.index');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/tokens', [ApiTokenController::class, 'store'])->name('tokens.store');
        Route::delete('/tokens/current', [ApiTokenController::class, 'destroy'])->name('tokens.destroy');
        Route::post('/books', [BookController::class, 'store'])->middleware('ability:write')->name('books.store');
        Route::post('/books/{book}/publish', [BookController::class, 'publish'])->middleware('ability:write')->name('books.publish');
        Route::post('/courses', [CourseController::class, 'store'])->middleware('ability:write')->name('courses.store');
        Route::post('/courses/{course}/publish', [CourseController::class, 'publish'])->middleware('ability:write')->name('courses.publish');
        Route::post('/jobs', [JobController::class, 'store'])->middleware('ability:write')->name('jobs.store');
        Route::post('/jobs/{job}/publish', [JobController::class, 'publish'])->middleware('ability:write')->name('jobs.publish');
        Route::post('/jobs/{job}/applications', [JobController::class, 'apply'])->middleware('ability:write')->name('jobs.apply');
        Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
        Route::post('/cart/books/{book}', [CartController::class, 'addBook'])->middleware('ability:write')->name('cart.books.add');
        Route::post('/cart/courses/{course}', [CartController::class, 'addCourse'])->middleware('ability:write')->name('cart.courses.add');
        Route::delete('/cart/items/{item}', [CartController::class, 'remove'])->name('cart.items.remove');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders', [OrderController::class, 'store'])->middleware('ability:write')->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/checkout', [OrderController::class, 'checkout'])->middleware('ability:write')->name('orders.checkout');
    });
});
