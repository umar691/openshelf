<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'course_module_id', 'position', 'title', 'content_markdown', 'video_url',
        'duration_seconds', 'is_preview', 'is_published',
    ];

    protected function casts(): array
    {
        return ['is_preview' => 'boolean', 'is_published' => 'boolean'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LessonAttachment::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }
}
