<?php

namespace App\Jobs;

use App\Models\Claim;
use App\Models\Insurer;
use App\Models\Provider;
use App\Services\InsuranceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessProviderClaims implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 3600*5;
    /**
     * Create a new job instance.
     */

    /**
     * The number of seconds before the job should be retried.
     *
     * @var int
     */
    public $retryAfter = 120; // Retry the job after 2 minutes if it fails or is delayed

    /**
     * Create a new job instance.
     */
    public function __construct(protected Insurer $insurer, protected Provider $provider)
    {
        //
    }

    /**
     * If the job fails, retry it until the given time
     *
     * @return \Illuminate\Support\Carbon
     */
    public function retryUntil()
    {
        return now()->addHours(2);
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $service = new InsuranceService();
        $batch = $service->createBatch($this->provider, $this->insurer, '');
        $service->attachBatchToClaims($this->insurer, $batch, $this->provider);

    }
}
