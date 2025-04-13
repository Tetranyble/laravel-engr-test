<?php

namespace Tests\Unit;

use App\Enum\EncounterDateType;
use App\Models\Batch;
use App\Models\Insurer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InsurerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_required_field_columns()
    {
        $this->assertTrue(Schema::hasColumns('insurers', [
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
        ]));
    }

    public function test_it_creates_an_insurer_with_valid_data()
    {
        $insurer = Insurer::create([
            'name' => 'Test Insurer',
            'code' => 'TI001',
            'email' => 'insurer@example.com',
            'preferred_date_type' => 'encounter_date',
            'specialty_multipliers' => json_encode(['cardiology' => 1.2, 'dermatology' => 1.1]),
            'priority_multipliers' => json_encode(['1' => 1.0, '5' => 1.5]),
            'daily_capacity' => 50000,
            'min_batch_size' => 1000,
            'max_batch_size' => 10000,
            'month_min_percent_limit' => 10.0,
            'month_max_percent_limit' => 80.0,
            'base_processing_cost' => 200.00,
        ]);

        $this->assertDatabaseHas('insurers', ['code' => 'TI001']);
        $this->assertEquals('Test Insurer', $insurer->name);
        $this->assertEquals(1.2, json_decode($insurer->specialty_multipliers, true)['cardiology']);
    }

    public function test_code_must_be_unique()
    {
        Insurer::factory()->create(['code' => 'DUPLICATE_CODE']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Insurer::create([
            'name' => 'Another',
            'code' => 'DUPLICATE_CODE',
            'email' => 'another@example.com',
            'preferred_date_type' => 'encounter_date',
            'specialty_multipliers' => json_encode(['general' => 1.0]),
            'priority_multipliers' => json_encode(['1' => 1.0]),
            'daily_capacity' => 1000,
            'min_batch_size' => 100,
            'max_batch_size' => 5000,
            'month_min_percent_limit' => 5,
            'month_max_percent_limit' => 50,
            'base_processing_cost' => 100,
        ]);
    }

    public function test_it_has_valid_default_for_preferred_date_type()
    {
        $insurer = Insurer::factory()->create([
            'preferred_date_type' => EncounterDateType::SUBMISSION_DATE,
        ]);

        $this->assertEquals(EncounterDateType::SUBMISSION_DATE, $insurer->preferred_date_type);
    }

    public function test_specialty_multipliers_should_be_valid_json()
    {
        $insurer = Insurer::factory()->create([
            'specialty_multipliers' => json_encode(['neurology' => 1.4]),
        ]);

        $multipliers = json_decode($insurer->specialty_multipliers, true);

        $this->assertIsArray($multipliers);
        $this->assertEquals(1.4, $multipliers['neurology']);
    }

    public function test_insurer_has_many_batches()
    {
        $insurer = Insurer::factory()->create();

        $batch1 = Batch::factory()->create(['insurer_id' => $insurer->id]);
        $batch2 = Batch::factory()->create(['insurer_id' => $insurer->id]);

        $this->assertTrue($insurer->batches->contains($batch1));
        $this->assertTrue($insurer->batches->contains($batch2));
        $this->assertCount(2, $insurer->batches);
    }

    public function test_claim_batch_count_returns_sum_of_claim_counts()
    {
        $insurer = Insurer::factory()->create();

        Batch::factory()->create(['insurer_id' => $insurer->id, 'claim_count' => 5]);
        Batch::factory()->create(['insurer_id' => $insurer->id, 'claim_count' => 10]);

        $this->assertEquals(15, $insurer->claimBatchCount());
    }

    public function test_is_daily_capacity_exhausted_returns_true_when_limit_reached()
    {
        $insurer = Insurer::factory()->create(['daily_capacity' => 10]);

        Batch::factory()->create(['insurer_id' => $insurer->id, 'claim_count' => 5]);
        Batch::factory()->create(['insurer_id' => $insurer->id, 'claim_count' => 5]);

        $this->assertTrue($insurer->isDailyCapacityExhausted());
    }

    public function test_is_daily_capacity_exhausted_returns_false_when_under_capacity()
    {
        $insurer = Insurer::factory()->create(['daily_capacity' => 20]);

        Batch::factory()->create(['insurer_id' => $insurer->id, 'claim_count' => 6]);
        Batch::factory()->create(['insurer_id' => $insurer->id, 'claim_count' => 8]);

        $this->assertFalse($insurer->isDailyCapacityExhausted());
    }

    public function test_it_counts_claims_only_for_today()
    {
        // Create an insurer
        $insurer = Insurer::factory()->create();

        // Create a batch for today
        $batchToday = Batch::factory()->create([
            'insurer_id' => $insurer->id,
            'claim_count' => 5,
            'created_at' => Carbon::today(), // Created today
        ]);

        // Create a batch for yesterday
        $batchYesterday = Batch::factory()->create([
            'insurer_id' => $insurer->id,
            'claim_count' => 10,
            'created_at' => Carbon::yesterday(), // Created yesterday
        ]);

        // Create a batch for tomorrow
        $batchTomorrow = Batch::factory()->create([
            'insurer_id' => $insurer->id,
            'claim_count' => 15,
            'created_at' => Carbon::tomorrow(), // Created tomorrow
        ]);

        // Assert that claimBatchCount() only counts the batch for today
        $this->assertEquals(5, $insurer->claimBatchCount());
    }

    public function test_it_returns_zero_when_no_claims_today()
    {
        // Create an insurer
        $insurer = Insurer::factory()->create();

        // Create a batch for yesterday
        Batch::factory()->create([
            'insurer_id' => $insurer->id,
            'claim_count' => 10,
            'created_at' => Carbon::yesterday(), // Created yesterday
        ]);

        // Assert that claimBatchCount() returns 0 when no claims are created today
        $this->assertEquals(0, $insurer->claimBatchCount());
    }
}
