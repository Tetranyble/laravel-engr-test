<?php

namespace App\Jobs;

use App\Services\InsuranceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInsurerBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(protected InsuranceService $insuranceService)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->insuranceService->insurers(function ($insurer){

            $this->insuranceService->providers($insurer)
                ->each(fn($provider) => ProcessProviderClaims::dispatch($insurer, $provider));


        });
    }



}
