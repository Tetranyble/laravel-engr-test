<?php

namespace Tests\Unit;

use App\Enum\EncounterDateType;
use App\Models\Batch;
use App\Models\Insurer;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_batches_table_has_expected_columns()
    {
        $this->assertTrue(Schema::hasTable('batches'));

        $this->assertTrue(Schema::hasColumns('batches', [
            'id',
            'provider_id',
            'insurer_id',
            'processing_date',
            'preferred_date_type',
            'total_value',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_batch_belongs_to_a_provider()
    {
        $provider = Provider::factory()->create();
        $batch = Batch::factory()->create(['provider_id' => $provider->id]);

        $this->assertInstanceOf(Provider::class, $batch->provider);
        $this->assertEquals($provider->id, $batch->provider->id);
    }

    public function test_batch_belongs_to_an_insurer()
    {
        $insurer = Insurer::factory()->create();
        $batch = Batch::factory()->create(['insurer_id' => $insurer->id]);

        $this->assertInstanceOf(Insurer::class, $batch->insurer);
        $this->assertEquals($insurer->id, $batch->insurer->id);
    }

    public function test_batch_has_the_correct_date()
    {
        $batch = Batch::factory()->create(['processing_date' => '2025-05-10']);


        $this->assertEquals('2025-05-10', $batch->processing_date->format('Y-m-d'));
    }

    public function test_batch_has_the_correct_default_preferred_date_type()
    {
        $batch = Batch::factory()->create();

        $this->assertEquals(EncounterDateType::SUBMISSION_DATE, $batch->preferred_date_type);
    }

    public function test_batch_has_default_total_value()
    {
        $batch = Batch::factory()->create([
            'total_value' => 0,
        ]);

        $this->assertEquals(0, $batch->total_value);
    }

    public function test_batch_can_be_created_with_specific_values()
    {
        $provider = Provider::factory()->create();
        $insurer = Insurer::factory()->create();

        $batch = Batch::factory()->create([
            'provider_id' => $provider->id,
            'insurer_id' => $insurer->id,
            'processing_date' => '2025-06-15',
            'preferred_date_type' => EncounterDateType::ENCOUNTER_DATE->value,
            'total_value' => 50000.00,
        ]);

        $this->assertEquals('2025-06-15', $batch->processing_date->format('Y-m-d'));
        $this->assertEquals(EncounterDateType::ENCOUNTER_DATE, $batch->preferred_date_type);
        $this->assertEquals(50000.00, $batch->total_value);
        $this->assertEquals($provider->id, $batch->provider_id);
        $this->assertEquals($insurer->id, $batch->insurer_id);
    }
}
