<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'user_id', 'gateway', 'gateway_reference', 'idempotency_key', 'amount_minor',
        'currency', 'status', 'gateway_metadata', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['gateway_metadata' => 'array', 'paid_at' => 'datetime'];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
