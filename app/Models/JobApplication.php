<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    protected $fillable = ['job_post_id', 'applicant_id', 'cover_letter', 'status', 'resume_path', 'submitted_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JobPost::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }
}
