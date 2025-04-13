<?php

namespace App\Services;

// app/Services/ClaimService.php

namespace App\Services;

use App\Enum\EncounterDateType;
use App\Models\Batch;
use App\Models\Claim;
use App\Models\Insurer;
use App\Notifications\ClaimBatchNotification;
use Carbon\Carbon;

class BatchService
{
    public function processClaim(Claim $claim)
    {

        // Find or create optimal batch
        $batch = $this->assignToOptimalBatch($claim, $claim->insurer);

        // Update claim with batch info
        $claim->update([
            'batch_id' => $batch->id,
        ]);

        // Send notification if batch is ready for processing
        if ($this->shouldNotifyInsurer($batch)) {
            $batch->notify(new ClaimBatchNotification($batch));
        }

        return $claim;
    }

    protected function assignToOptimalBatch(Claim $claim, Insurer $insurer)
    {
        // Determine batch date based on insurer preference
        $batchDate = $insurer->preferred_date_type === EncounterDateType::ENCOUNTER_DATE
            ? $claim->encounter_date
            : $claim->submission_date;

        $batchIdentifier = $claim->provider->name.' '.$batchDate->format('M j Y');

        if (($insurer->isDailyCapacityExhausted() === false) && $batch = $insurer->findBatch($batchIdentifier)) {
            // Update existing batch
            $type = $insurer->preferred_date_type === EncounterDateType::ENCOUNTER_DATE
                ? EncounterDateType::ENCOUNTER_DATE
                : EncounterDateType::SUBMISSION_DATE;

            $batch->increment('claim_count');
            $batch->total_value += $claim->calculateClaimCost($type);
            $batch->save();

            return $batch;
        }

        // Create new batch
        $processingDate = Carbon::parse($batchDate)->addDay();

        $type = $insurer->preferred_date_type === EncounterDateType::ENCOUNTER_DATE
            ? EncounterDateType::ENCOUNTER_DATE
            : EncounterDateType::SUBMISSION_DATE;

        return Batch::create([
            'batch_identifier' => $batchIdentifier,
            'insurer_id' => $insurer->id,
            'provider_id' => $claim->provider_id,
            'processing_date' => $processingDate,
            'total_value' => $claim->calculateClaimCost($type),
            'claim_count' => 1,
        ]);
    }

    protected function shouldNotifyInsurer(Batch $batch)
    {
        // Notify if batch reaches min size or is scheduled for tomorrow
        return $batch->claim_count >= $batch->insurer->min_batch_size ||
            $batch->processing_date->isTomorrow();
    }
}
