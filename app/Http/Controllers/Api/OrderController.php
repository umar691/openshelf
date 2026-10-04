<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\StartCheckoutRequest;
use App\Models\Order;
use App\Services\Marketplace\OrderService;
use App\Services\Payments\PaymentOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->orders()->with('items')->latest()->paginate(20));
    }

    public function store(CreateOrderRequest $request, OrderService $orders): JsonResponse
    {
        $order = $orders->createFromActiveCart($request->user(), $request->validated());

        return response()->json(['data' => $order], 201);
    }

    public function show(Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        return response()->json(['data' => $order->load('items')]);
    }

    public function checkout(
        StartCheckoutRequest $request,
        Order $order,
        PaymentOrchestrator $payments,
    ): JsonResponse {
        Gate::authorize('view', $order);
        $result = $payments->beginCheckout(
            $request->validated('gateway'),
            $order,
            $request->user()->id,
            $request->user()->email,
            route('home').'?checkout=success&session_id={CHECKOUT_SESSION_ID}',
            route('home').'?checkout=cancelled',
        );

        return response()->json([
            'payment' => $result['transaction'],
            'checkout_url' => $result['checkout']->redirectUrl,
        ], 201);
    }
}
