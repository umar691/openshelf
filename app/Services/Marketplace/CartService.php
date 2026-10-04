<?php

namespace App\Services\Marketplace;

use App\Models\Book;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function addBook(User $user, Book $book, int $quantity, string $format, string $fulfillment): CartItem
    {
        if ($book->status !== 'published') {
            throw ValidationException::withMessages(['book' => 'This book is not available for purchase.']);
        }

        if (! in_array($format, $book->formats ?? [], true)) {
            throw ValidationException::withMessages(['format' => 'The selected book format is unavailable.']);
        }

        if ($fulfillment === 'digital' && ! $book->digital_available) {
            throw ValidationException::withMessages(['fulfillment' => 'This title is not available as a digital edition.']);
        }

        if ($fulfillment === 'digital' && $format === 'print') {
            throw ValidationException::withMessages(['format' => 'Print editions must use physical fulfillment.']);
        }

        if ($fulfillment === 'physical' && ! $book->physical_available) {
            throw ValidationException::withMessages(['fulfillment' => 'This title is not available as a physical edition.']);
        }

        if ($fulfillment === 'physical' && $format !== 'print') {
            throw ValidationException::withMessages(['format' => 'Physical fulfillment is only available for print editions.']);
        }
        if ($fulfillment === 'digital' && $quantity !== 1) {
            throw ValidationException::withMessages(['quantity' => 'Digital editions can only be added one at a time.']);
        }

        return DB::transaction(function () use ($user, $book, $quantity, $format, $fulfillment): CartItem {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id, 'status' => 'active']);
            $cart = Cart::query()->lockForUpdate()->findOrFail($cart->id);

            $hasOtherCurrency = $cart->items()
                ->with('purchasable')
                ->get()
                ->contains(fn (CartItem $item) => $item->purchasable?->currency !== $book->currency);

            if ($hasOtherCurrency) {
                throw ValidationException::withMessages(['cart' => 'A cart can only contain items in one currency.']);
            }

            return $cart->items()->updateOrCreate(
                ['purchasable_type' => $book->getMorphClass(), 'purchasable_id' => $book->id],
                ['quantity' => $quantity, 'options' => ['format' => $format, 'fulfillment' => $fulfillment]],
            );
        });
    }

    public function addCourse(User $user, Course $course): CartItem
    {
        if ($course->status !== 'published') {
            throw ValidationException::withMessages(['course' => 'This course is not available for enrollment.']);
        }

        return DB::transaction(function () use ($user, $course): CartItem {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id, 'status' => 'active']);
            $cart = Cart::query()->lockForUpdate()->findOrFail($cart->id);

            $hasOtherCurrency = $cart->items()
                ->with('purchasable')
                ->get()
                ->contains(fn (CartItem $item) => $item->purchasable?->currency !== $course->currency);

            if ($hasOtherCurrency) {
                throw ValidationException::withMessages(['cart' => 'A cart can only contain items in one currency.']);
            }

            return $cart->items()->updateOrCreate(
                ['purchasable_type' => $course->getMorphClass(), 'purchasable_id' => $course->id],
                ['quantity' => 1, 'options' => ['fulfillment' => 'digital']],
            );
        });
    }
}
