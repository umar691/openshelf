<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DigitalEntitlement extends Model
{
    protected $fillable = ['user_id', 'entitlement_type', 'entitlement_id', 'order_item_id', 'granted_at'];

    protected function casts(): array
    {
        return ['granted_at' => 'datetime'];
    }

    public function entitlement(): MorphTo
    {
        return $this->morphTo();
    }
}
