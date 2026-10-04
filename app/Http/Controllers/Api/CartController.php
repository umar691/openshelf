<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddBookToCartRequest;
use App\Models\Book;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Course;
use App\Services\Marketplace\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $cart = Cart::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->with('items.purchasable')
            ->first();

        return response()->json(['data' => $cart]);
    }

    public function addBook(AddBookToCartRequest $request, Book $book, CartService $carts): JsonResponse
    {
        $data = $request->validated();
        $item = $carts->addBook(
            $request->user(),
            $book,
            $data['quantity'],
            $data['format'],
            $data['fulfillment'],
        );

        return response()->json(['data' => $item->load('purchasable')], 201);
    }

    public function addCourse(Request $request, Course $course, CartService $carts): JsonResponse
    {
        $item = $carts->addCourse($request->user(), $course);

        return response()->json(['data' => $item->load('purchasable')], 201);
    }

    public function remove(Request $request, CartItem $item): JsonResponse
    {
        $cart = Cart::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->whereKey($item->cart_id)
            ->firstOrFail();
        $cart->items()->whereKey($item->id)->delete();

        return response()->json(['removed' => true]);
    }
}
