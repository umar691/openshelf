<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gig extends Model
{
    protected $fillable = ['freelancer_id', 'title', 'slug', 'description', 'category', 'status'];

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(GigPackage::class);
    }
}
