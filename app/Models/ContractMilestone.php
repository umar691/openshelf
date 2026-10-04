<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractMilestone extends Model
{
    protected $fillable = [
        'escrow_contract_id', 'position', 'title', 'description', 'amount_minor',
        'status', 'due_at', 'submitted_at', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'submitted_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(EscrowContract::class, 'escrow_contract_id');
    }
}
