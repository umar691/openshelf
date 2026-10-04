<?php

namespace App\Models;

use App\Models\Concerns\HasReviews;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasReviews;

    protected $fillable = [
        'instructor_id', 'title', 'slug', 'description', 'subject', 'difficulty',
        'status', 'price_minor', 'currency', 'cover_image', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('position');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }
}
