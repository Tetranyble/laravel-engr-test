<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClaimRequest;
use App\Http\Resources\ClaimResource;
use App\Models\Batch;
use App\Services\BatchService;
use App\Services\ClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function __construct(protected ClaimService $claimService, protected BatchService $batchService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClaimRequest $request): JsonResponse
    {

        $claim = $this->batchService->processClaim(
            $this->claimService->processClaim($request->validated())
        );

        return response()->json(new ClaimResource($claim), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    private function checkConstraints($insurer, $batch, $processingDate, $totalValue)
    {
        // Check daily capacity (sum of all batches processed on $processingDate)
        $dailyTotal = Batch::where('insurer_id', $insurer->id)
            ->whereDate('processing_date', $processingDate)
            ->sum('total_value');

        if ($dailyTotal + $totalValue > $insurer->daily_capacity) {
            return false;
        }

        // Check batch size constraints
        $batchTotal = $batch->claims()->sum('total_value') + $totalValue;
        if ($batchTotal > $insurer->max_batch_size || $batchTotal < $insurer->min_batch_size) {
            return false;
        }

        return true;
    }
}
