<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'purchasable_type', 'purchasable_id', 'quantity', 'options'];

    protected function casts(): array
    {
        return ['options' => 'array'];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }
}
