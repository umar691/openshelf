<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookChapter extends Model
{
    protected $fillable = ['book_id', 'chapter_number', 'title', 'body_markdown', 'is_preview'];

    protected function casts(): array
    {
        return ['is_preview' => 'boolean'];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
