<?php

namespace App\Services;

use App\Enum\EncounterDateType;
use App\Models\Batch;
use App\Models\Claim;
use App\Models\Insurer;
use App\Models\Provider;
use App\Notifications\ClaimBatchNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InsuranceService
{

    public function processingDay()
    {
        return Carbon::today()->subDay();

    }

    public function providerName(Provider $provider, Insurer $insurer, Claim $claim)
    {
        $batchDate = $insurer->preferred_date_type === EncounterDateType::ENCOUNTER_DATE
            ? $claim->encounter_date
            : $claim->submission_date;

       return $provider->name.' '.$batchDate->format('M j Y');
    }

    public function insurers(callable $callBack,$count = 5000)
    {
        Insurer::chunk($count, function ($insurers) use($callBack){
            $insurers->each(function ($insurer) use($callBack){
                $callBack($insurer);
            });
        });
    }

    public function providers(Insurer $insurer)
    {
        return Provider::whereExists(function ($q) use ($insurer) {
            $q->select(DB::raw(1))
                ->from('claims')
                ->whereRaw('claims.provider_id = providers.id')
                ->whereNull('claims.batch_id')
                ->where('claims.insurer_id', $insurer->id);
        })->get();

    }

    public function createBatch(Provider $provider, Insurer $insurer, string $identifier)
    {
        return Batch::updateOrCreate([
            'provider_id' => $provider->id,
            'insurer_id' => $insurer->id,
            'processing_date'  => $this->processingDay(),
            'batch_identifier' => $identifier,
        ],[
            'provider_id' => $provider->id,
            'insurer_id' => $insurer->id,
            'processing_date'  => $this->processingDay(),
            'preferred_date_type' => $insurer->preferred_date_type,
            'batch_identifier' => $identifier,
        ]);
    }
    public function attachBatchToClaims(Insurer $insurer, Batch $batch, Provider $provider): void
    {
        $claim = null;
        Claim::where('provider_id', $provider->id)
            ->whereNull('batch_id')
            ->chunk($insurer->max_batch_size, function ($claims) use($batch, &$claim){
                $claim = $claims->first();
                $batch->total_value += $claims->sum('total_value');
                $batch->claim_count += $claims->count();
                $claims->save();

                $claims->each(fn($claim) => $claim->update(['batch_id' => $batch->id]) );

            });
        if ($batch){
            $batch->update([
                'batch_identifier' => $this->providerName($provider,$insurer, $claim)
            ]);
            $batch->notify(new ClaimBatchNotification($batch));
        }

    }
}
