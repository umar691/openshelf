<?php

namespace App\Services\Marketplace;

use App\Models\Order;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function releaseExpiredReservations(): int
    {
        return DB::transaction(function (): int {
            $reservations = StockReservation::query()
                ->where('status', 'reserved')
                ->where('expires_at', '<=', now())
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $reservation->book()->lockForUpdate()->firstOrFail()->increment('stock_quantity', $reservation->quantity);
                $reservation->update(['status' => 'released', 'released_at' => now()]);
                $order = Order::query()->whereHas('items', fn ($query) => $query->whereKey($reservation->order_item_id))->first();
                if ($order?->payment_status === 'unpaid') {
                    $order->update(['status' => 'cancelled', 'payment_status' => 'expired']);
                    $order->paymentTransactions()->where('status', 'pending')->update(['status' => 'expired']);
                }
            }

            return $reservations->count();
        });
    }

    public function releaseForFailedOrder(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $reservations = StockReservation::query()
                ->whereIn('order_item_id', $order->items()->select('id'))
                ->where('status', 'reserved')
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $reservation->book()->lockForUpdate()->firstOrFail()->increment('stock_quantity', $reservation->quantity);
                $reservation->update(['status' => 'released', 'released_at' => now()]);
            }
        });
    }
}
