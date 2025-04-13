<?php

namespace Tests\Unit;

use App\Enum\EncounterDateType;
use App\Models\Batch;
use App\Models\Claim;
use App\Models\Insurer;
use App\Models\Provider;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_claims_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('claims'), 'Table "claims" does not exist');
        $this->assertTrue(Schema::hasColumns('claims',
            [
                'id',
                'provider_id',
                'insurer_id',
                'specialty',
                'batch_id',
                'encounter_date',
                'submission_date',
                'priority_level',
                'total_value',
                'created_at',
                'updated_at',
            ]), 'Missing column(s)');
    }

    public function test_claim_belongs_to_a_provider()
    {
        $provider = Provider::factory()->create();
        $claim = Claim::factory()->create(['provider_id' => $provider->id]);

        $this->assertInstanceOf(Provider::class, $claim->provider);
        $this->assertEquals($provider->id, $claim->provider->id);
    }

    public function test_claim_belongs_to_an_insurer()
    {
        $insurer = Insurer::factory()->create();
        $claim = Claim::factory()->create(['insurer_id' => $insurer->id]);

        $this->assertInstanceOf(Insurer::class, $claim->insurer);
        $this->assertEquals($insurer->id, $claim->insurer->id);
    }

    public function test_claim_belongs_to_a_batch_if_set()
    {
        $batch = Batch::factory()->create();
        $claim = Claim::factory()->create(['batch_id' => $batch->id]);

        $this->assertInstanceOf(Batch::class, $claim->batch);
        $this->assertEquals($batch->id, $claim->batch->id);
    }

    public function test_claim_batch_can_be_null()
    {
        $claim = Claim::factory()->create(['batch_id' => null]);

        $this->assertNull($claim->batch);
    }

    public function test_calculate_date_cost_multiplier()
    {
        $insurer = Insurer::factory()->create([
            'month_min_percent_limit' => 0.2,
            'month_max_percent_limit' => 0.5,
        ]);

        $claim = Claim::factory()->make([
            'insurer_id' => $insurer->id,
        ]);

        $claim->setRelation('insurer', $insurer);

        $multiplier = $claim->calculateDateCostMultiplier(15); // Mid-month

        // Should fall between 0.2 and 0.5
        $this->assertGreaterThanOrEqual(0.2, $multiplier);
        $this->assertLessThanOrEqual(0.5, $multiplier);
    }

    public function test_calculate_specialty_multiplier()
    {
        $this->markTestSkipped();
        $insurer = Insurer::factory()->create([
            'specialty_multipliers' => json_encode(['cardiology' => 1.5]),
        ]);

        $claim = Claim::factory()->make([
            'specialty' => 'cardiology',
        ]);

        $claim->setRelation('insurer', $insurer);

        $this->assertEquals(1.5, $claim->calculateSpecialtyMultiplier());

        // Fallback to 1
        $claim->specialty = 'unknown';
        $this->assertEquals(1.0, $claim->calculateSpecialtyMultiplier());
    }

    public function test_calculate_priority_multiplier()
    {
        $this->markTestSkipped();
        $insurer = Insurer::factory()->create([
            'priority_multipliers' => json_encode(['1' => 1.0, '5' => 1.5]),
        ]);

        $claim = Claim::factory()->make([
            'priority_level' => 5,
        ]);

        $claim->setRelation('insurer', $insurer);

        $this->assertEquals(1.5, $claim->calculatePriorityMultiplier());

        $claim->priority_level = 3;
        $this->assertEquals(1.0, $claim->calculatePriorityMultiplier()); // Default
    }

    public function test_value_cost_multiplier()
    {
        $claim = Claim::factory()->make([
            'total_amount' => 2000,
        ]);

        $this->assertEquals(2.0, $claim->valueCostMultiplier());
    }

    public function test_calculate_claim_cost()
    {
        $this->markTestSkipped();
        $insurer = Insurer::factory()->create([
            'base_processing_cost' => 100,
            'month_min_percent_limit' => 0.2,
            'month_max_percent_limit' => 0.5,
            'specialty_multipliers' => json_encode(['cardiology' => 2]),
            'priority_multipliers' => json_encode(['1' => 1.5]),
        ]);

        $claim = Claim::factory()->make([
            'specialty' => 'cardiology',
            'priority_level' => 1,
            'total_amount' => 3000,
            'encounter_date' => Carbon::createFromDate(null, null, 10),
            'submission_date' => Carbon::createFromDate(null, null, 15),
        ]);

        $claim->setRelation('insurer', $insurer);

        $cost = $claim->calculateClaimCost(EncounterDateType::ENCOUNTER_DATE);

        $this->assertIsFloat($cost);
        $this->assertGreaterThan(0, $cost);
    }
}
