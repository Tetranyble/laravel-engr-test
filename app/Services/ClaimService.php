<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\Insurer;
use App\Models\Provider;

class ClaimService
{
    public function processClaim(array $claimData): Claim
    {
        $insurer = Insurer::where('code', $claimData['insurer_code'])->firstOrFail();
        $provider = Provider::where('name', $claimData['provider_name'])->firstOrFail();

        // Calculate total value
        $totalValue = collect($claimData['items'])->sum(function ($item) {
            return $item['unit_price'] * $item['quantity'];
        });

        // Create claim
        $claim = Claim::create([
            'provider_id' => $provider->id,
            'insurer_id' => $insurer->id,
            'encounter_date' => $claimData['encounter_date'],
            'submission_date' => now()->toDateString(),
            'priority_level' => $claimData['priority_level'],
            'specialty' => $claimData['specialty'],
            'total_value' => $totalValue,
        ]);

        // Create claim items
        $this->associateCliamItems($claim, $claimData['items']);

        return $claim;

    }

    protected function associateCliamItems(Claim $claim, array $items): void
    {
        foreach ($items as $item) {
            $claim->items()->create([
                'name' => $item['name'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
            ]);
        }
    }
}
