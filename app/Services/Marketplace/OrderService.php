<?php

namespace App\Services\Marketplace;

use App\Models\Book;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Course;
use App\Models\Order;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly OrderFulfillmentService $fulfillment) {}

    public function createFromActiveCart(User $user, array $addresses = []): Order
    {
        return DB::transaction(function () use ($user, $addresses): Order {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->firstOrFail();
            $items = $cart->items()->with('purchasable')->lockForUpdate()->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Add at least one item before placing an order.']);
            }

            $lines = $items->map(fn (CartItem $item): array => $this->priceLine($item));
            $currencies = $lines->pluck('currency')->unique();
            if ($currencies->count() !== 1) {
                throw ValidationException::withMessages(['cart' => 'A cart can only contain items in one currency.']);
            }

            $currency = $currencies->first();
            $subtotal = $lines->sum(fn (array $line): int => $line['unit_price_minor'] * $line['quantity']);
            $requiresShipping = $lines->contains(fn (array $line): bool => $line['fulfillment'] === 'physical');
            if ($requiresShipping && empty($addresses['shipping_address'])) {
                throw ValidationException::withMessages(['shipping_address' => 'A shipping address is required for physical items.']);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'OS-'.Str::ulid(),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'fulfillment_status' => 'unfulfilled',
                'currency' => $currency,
                'subtotal_minor' => $subtotal,
                'tax_minor' => 0,
                'shipping_minor' => 0,
                'total_minor' => $subtotal,
                'billing_address' => $addresses['billing_address'] ?? null,
                'shipping_address' => $addresses['shipping_address'] ?? null,
                'placed_at' => now(),
            ]);

            foreach ($lines as $line) {
                $orderItem = $order->items()->create([
                    'purchasable_type' => $line['product']->getMorphClass(),
                    'purchasable_id' => $line['product']->getKey(),
                    'title' => $line['product']->title,
                    'quantity' => $line['quantity'],
                    'unit_price_minor' => $line['unit_price_minor'],
                    'currency' => $line['currency'],
                    'metadata' => $line['metadata'],
                ]);

                if ($line['fulfillment'] === 'physical') {
                    $book = $line['product'];
                    $book->decrement('stock_quantity', $line['quantity']);
                    StockReservation::create([
                        'book_id' => $book->id,
                        'order_item_id' => $orderItem->id,
                        'quantity' => $line['quantity'],
                        'status' => 'reserved',
                        'expires_at' => now()->addMinutes(30),
                    ]);
                }
            }

            $cart->update(['status' => 'checked_out']);

            if ($subtotal === 0) {
                $order->update([
                    'status' => 'paid',
                    'payment_status' => 'paid',
                    'fulfillment_status' => 'processing',
                ]);
                $this->fulfillment->fulfillPaidOrder($order);
            }

            return $order->load('items');
        });
    }

    private function priceLine(CartItem $item): array
    {
        $product = $this->lockProduct($item->purchasable);
        $options = $item->options ?? [];
        $fulfillment = $options['fulfillment'] ?? 'digital';

        if ($product instanceof Book) {
            $format = $options['format'] ?? '';
            if ($product->status !== 'published' || ! in_array($format, $product->formats ?? [], true)) {
                throw ValidationException::withMessages(['cart' => 'A selected book is no longer available in this format.']);
            }

            if ($fulfillment === 'physical') {
                if ($format !== 'print' || ! $product->physical_available || $product->stock_quantity === null || $product->stock_quantity < $item->quantity) {
                    throw ValidationException::withMessages(['cart' => 'There is not enough physical stock for a selected book.']);
                }
            } elseif ($fulfillment !== 'digital' || $format === 'print' || ! $product->digital_available || $item->quantity !== 1) {
                throw ValidationException::withMessages(['cart' => 'A selected digital book is no longer available.']);
            }

            return [
                'product' => $product,
                'quantity' => $item->quantity,
                'unit_price_minor' => $product->price_minor,
                'currency' => $product->currency,
                'fulfillment' => $fulfillment,
                'metadata' => ['format' => $format, 'fulfillment' => $fulfillment],
            ];
        }

        if ($product instanceof Course && $product->status === 'published' && $fulfillment === 'digital') {
            return [
                'product' => $product,
                'quantity' => 1,
                'unit_price_minor' => $product->price_minor,
                'currency' => $product->currency,
                'fulfillment' => 'digital',
                'metadata' => ['fulfillment' => 'digital'],
            ];
        }

        throw ValidationException::withMessages(['cart' => 'A selected item is no longer available.']);
    }

    private function lockProduct(?Model $product): Model
    {
        if (! $product instanceof Book && ! $product instanceof Course) {
            throw ValidationException::withMessages(['cart' => 'A selected item type cannot be purchased.']);
        }

        return $product->newQuery()->lockForUpdate()->findOrFail($product->getKey());
    }
}
