<?php

namespace App\Models;

use App\Traits\CostOptimization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    use CostOptimization, HasFactory;

    protected $fillable = [
        'provider_id',
        'insurer_id',
        'specialty',
        'batch_id',
        'encounter_date',
        'submission_date',
        'priority_level',
        'total_value',
        'submission_weight',
        'encounter_weight'
    ];

    protected $casts = [
        'encounter_date' => 'datetime',
        'submission_date' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function insurer(): BelongsTo
    {
        return $this->belongsTo(Insurer::class, 'insurer_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function items()
    {
        return $this->hasMany(ClaimItem::class, 'claim_id');
    }
}
