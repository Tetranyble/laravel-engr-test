<?php

namespace App\Models;

use App\Enum\EncounterDateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insurer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'email',
        'preferred_date_type',
        'specialty_multipliers',
        'priority_multipliers',
        'daily_capacity',
        'min_batch_size',
        'max_batch_size',
        'month_min_percent_limit',
        'month_max_percent_limit',
        'base_processing_cost',
    ];

    protected $casts = [
        'specialty_multipliers' => 'array',
        'priority_multipliers' => 'array',
        'preferred_date_type' => EncounterDateType::class,
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'insurer_id');
    }

    public function claimBatchCount(): int
    {
        return $this->batches()
            ->whereDate('created_at', now()->today())
            ->sum('claim_count');
    }

    public function isDailyCapacityExhausted(): bool
    {
        return $this->claimBatchCount() >= $this->daily_capacity;
    }

    public function currentBatchIdentifier(Claim $claim): string
    {
        $batchDate = $this->preferred_date_type === EncounterDateType::ENCOUNTER_DATE
            ? $claim->encounter_date
            : $claim->submission_date;

        return $claim->provider->name.' '.$batchDate->format('M j Y');
    }

    public function findBatch(string $batchIdentifier = '')
    {
        return $this->batches()->where('batch_identifier', $batchIdentifier)
            //->where('provider_id', $batchIdentifier)
            ->where('insurer_id', $this->id)
            ->first();
    }
}
