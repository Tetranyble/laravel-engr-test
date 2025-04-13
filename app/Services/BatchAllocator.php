<?php

namespace App\Services;

use App\Enum\EncounterDateType;
use App\Models\Batch;
use App\Models\Claim;
use App\Models\DailyCapacity;
use Carbon\Carbon;

class BatchAllocator
{
    public function allocate(Claim $claim): ?Batch
    {
        $datesToCheck = $this->getBatchDateOptions($claim);

        $bestBatch = null;
        $bestCost = INF;

        foreach ($datesToCheck as [$batchDate, $dateType]) {

            if (! $batchDate) {
                continue;
            }

            $batch = $this->makeBatchCandidate($claim, $batchDate, $dateType);

            if (! $this->isBatchValid($claim, $batch)) {
                continue;
            }

            if (! $this->hasCapacity($claim, $batch)) {
                continue;
            }

            $cost = $claim->calculateClaimCost($dateType);

            if ($cost < $bestCost) {
                $bestBatch = $batch;
                $bestCost = $cost;
            }
        }

        if (! $bestBatch) {

            return $this->allocateInFuture($claim);
        }

        if (! $bestBatch->exists) {
            $bestBatch->total_value = $claim->total_value;
            $bestBatch->save();
        }

        return $bestBatch;
    }

    protected function getBatchDateOptions(Claim $claim): array
    {
        $insurer = $claim->insurer;

        $preferredType = $insurer->preferred_date_type;

        $preferredDate = $claim->{$preferredType};

        $alternativeType = $preferredType === EncounterDateType::ENCOUNTER_DATE->value
            ? EncounterDateType::SUBMISSION_DATE->value
            : EncounterDateType::ENCOUNTER_DATE->value;

        $alternativeDate = $claim[$alternativeType];

        return [
            [$preferredDate, $preferredType],
            [$alternativeDate, $alternativeType],
        ];
    }

    protected function makeBatchCandidate(Claim $claim, $batchDate, EncounterDateType|string $dateType): Batch
    {
        return Batch::firstOrNew([
            'insurer_id' => $claim->insurer_id,
            'provider_id' => $claim->provider_id,
            'date' => $batchDate,
            'preferred_date_type' => $dateType,
        ]);
    }

    protected function isBatchValid(Claim $claim, Batch $batch): bool
    {
        $tentativeTotal = $batch->total_value + $claim->total_value;
        $insurer = $claim->insurer;

        return $tentativeTotal <= $insurer->max_batch_size &&
            $tentativeTotal >= $insurer->min_batch_size;
    }

    protected function hasCapacity(Claim $claim, Batch $batch): bool
    {
        $insurer = $claim->insurer;
        $processingDate = Carbon::parse($batch->date)->addDay();

        $capacity = DailyCapacity::firstOrNew([
            'insurer_id' => $insurer->id,
            'processing_date' => $processingDate,
        ]);

        return $capacity->used_capacity + $claim->total_value <= $insurer->daily_capacity;
    }

    protected function allocateInFuture(Claim $claim): ?Batch
    {
        $insurer = $claim->insurer;
        $preferredType = $insurer->preferred_date_type;

        $start = Carbon::parse(max($claim->encounter_date, $claim->submission_date))->addDay();
        $end = now()->endOfMonth();

        while ($start->lte($end)) {
            $processingDate = $start->copy()->addDay();

            if ($claim->total_value < $insurer->min_batch_size) {
                $start->addDay();

                continue;
            }

            $capacity = DailyCapacity::firstOrNew([
                'insurer_id' => $insurer->id,
                'processing_date' => $processingDate,
            ]);


            if ($capacity->used_capacity + $claim->total_value <= $insurer->daily_capacity) {
                return Batch::create([
                    'insurer_id' => $insurer->id,
                    'provider_id' => $claim->provider_id,
                    'date' => $start->toDateString(),
                    'date_type' => $preferredType,
                    'total_value' => $claim->total_value,
                ]);
            }

            $start->addDay();
        }

        return null;
    }
}
