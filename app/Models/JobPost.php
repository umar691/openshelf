<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPost extends Model
{
    protected $fillable = [
        'employer_id', 'title', 'slug', 'description', 'requirements',
        'employment_type', 'location_type', 'location', 'salary_min_minor',
        'salary_max_minor', 'salary_currency', 'status', 'closes_at',
    ];

    protected function casts(): array
    {
        return ['requirements' => 'array', 'closes_at' => 'datetime'];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
