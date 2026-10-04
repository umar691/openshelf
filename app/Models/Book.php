<?php

namespace App\Models;

use App\Models\Concerns\HasReviews;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasReviews;

    protected $fillable = [
        'vendor_id', 'title', 'slug', 'description', 'cover_image', 'formats',
        'price_minor', 'currency', 'isbn', 'category', 'status',
        'digital_available', 'physical_available', 'stock_quantity', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'formats' => 'array',
            'digital_available' => 'boolean',
            'physical_available' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(BookChapter::class)->orderBy('chapter_number');
    }
}
