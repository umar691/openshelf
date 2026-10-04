<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonAttachment extends Model
{
    protected $fillable = ['lesson_id', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes'];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
