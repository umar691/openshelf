<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'summary',
        'body',
        'topic',
        'tags',
        'cover_image',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bookmarks(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bookmarks')->withTimestamps();
    }

    public function reactions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'content_reactions')->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
