<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    protected $fillable = ['book_id', 'order_item_id', 'quantity', 'status', 'expires_at', 'released_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'released_at' => 'datetime'];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
