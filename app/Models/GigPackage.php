<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GigPackage extends Model
{
    protected $fillable = [
        'gig_id', 'tier', 'title', 'description', 'price_minor', 'currency',
        'delivery_days', 'revisions', 'features',
    ];

    protected function casts(): array
    {
        return ['features' => 'array'];
    }

    public function gig(): BelongsTo
    {
        return $this->belongsTo(Gig::class);
    }
}
