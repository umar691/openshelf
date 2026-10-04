<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EscrowContract extends Model
{
    protected $fillable = [
        'client_id', 'freelancer_id', 'gig_id', 'job_application_id', 'title',
        'status', 'currency', 'total_minor', 'funded_minor', 'released_minor',
        'refunded_minor', 'funded_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['funded_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ContractMilestone::class)->orderBy('position');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function paymentTransactions(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable');
    }
}
