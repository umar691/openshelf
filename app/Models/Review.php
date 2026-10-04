<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    protected $fillable = ['user_id', 'rating', 'title', 'body', 'status'];

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
