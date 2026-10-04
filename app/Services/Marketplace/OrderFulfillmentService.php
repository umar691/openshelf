<?php

namespace App\Services\Marketplace;

use App\Models\Book;
use App\Models\Course;
use App\Models\DigitalEntitlement;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderFulfillmentService
{
    public function fulfillPaidOrder(Order $order): void
    {
        $order->loadMissing('items.purchasable');

        foreach ($order->items as $item) {
            if ($item->purchasable instanceof Book && data_get($item->metadata, 'fulfillment') === 'digital') {
                DigitalEntitlement::query()->firstOrCreate(
                    [
                        'user_id' => $order->user_id,
                        'entitlement_type' => $item->purchasable->getMorphClass(),
                        'entitlement_id' => $item->purchasable_id,
                    ],
                    ['order_item_id' => $item->id, 'granted_at' => now()],
                );
            }

            if ($item->purchasable instanceof Course) {
                $item->purchasable->enrollments()->firstOrCreate(
                    ['user_id' => $order->user_id],
                    ['status' => 'active', 'enrolled_at' => now()],
                );
            }
        }

        DB::table('stock_reservations')
            ->whereIn('order_item_id', $order->items()->select('id'))
            ->where('status', 'reserved')
            ->update(['status' => 'committed', 'updated_at' => now()]);
    }
}
