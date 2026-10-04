<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id', 'position', 'type', 'prompt', 'options', 'correct_answers', 'points', 'explanation',
    ];

    protected function casts(): array
    {
        return ['options' => 'array', 'correct_answers' => 'array'];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
