<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'order_number', 'status', 'payment_status', 'fulfillment_status',
        'currency', 'subtotal_minor', 'tax_minor', 'shipping_minor', 'total_minor',
        'billing_address', 'shipping_address', 'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'placed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentTransactions(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable');
    }
}
