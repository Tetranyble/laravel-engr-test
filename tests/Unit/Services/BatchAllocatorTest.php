<?php

namespace Tests\Unit\Services;

use App\Enum\EncounterDateType;
use App\Models\Batch;
use App\Models\Claim;
use App\Models\DailyCapacity;
use App\Models\Insurer;
use App\Models\Provider;
use App\Services\BatchAllocator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BatchAllocatorTest extends TestCase
{
    use RefreshDatabase;

    protected BatchAllocator $allocator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->allocator = new BatchAllocator();
    }

    public function test_allocate_creates_or_selects_best_batch()
    {
        $this->markTestSkipped();
        $allocator = Mockery::mock(BatchAllocator::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        // Create mock data for the insurer and claim
        $insurer = Insurer::factory()->create([
            'preferred_date_type' => EncounterDateType::ENCOUNTER_DATE->value,
        ]);

        $claim = Claim::factory()->make([
            'total_value' => 200,
            'encounter_date' => Carbon::today()->toDateString(),
            'submission_date' => Carbon::today()->toDateString(),
            'provider_id' => 1,
        ]);
        $claim->setRelation('insurer', $insurer);

        // Mock the methods used inside allocate to control their behavior
        $allocator
            ->shouldReceive('getBatchDateOptions')
            ->with($claim)
            ->once()
            ->andReturn([
                ['2024-04-10', EncounterDateType::ENCOUNTER_DATE->value],
                ['2024-04-11', EncounterDateType::SUBMISSION_DATE->value],
            ]);

        $allocator
            ->shouldReceive('makeBatchCandidate')
            ->twice()  // Called for each date
            ->andReturnUsing(function ($claim, $batchDate, $dateType) {
                return new Batch([
                    'insurer_id' => $claim->insurer_id,
                    'provider_id' => $claim->provider_id,
                    'date' => $batchDate,
                    'total_value' => $claim->total_value,
                    'preferred_date_type' => $dateType,
                ]);
            });

        // Mocking the isBatchValid and hasCapacity checks to always return true
        $allocator
            ->shouldReceive('isBatchValid')
            ->andReturn(true);

        $allocator
            ->shouldReceive('hasCapacity')
            ->andReturn(true);

        // Mocking calculateClaimCost to return a value that will be compared
        $allocator
            ->shouldReceive('calculateClaimCost')
            ->withAnyArgs()
            ->andReturn(150);  // Assuming this is the "best" cost

        // If no batch exists, we will mock a save operation to avoid the exception
        $allocator
            ->shouldReceive('allocateInFuture')
            ->andReturn(new Batch([
                'insurer_id' => $claim->insurer_id,
                'provider_id' => $claim->provider_id,
                'date' => Carbon::tomorrow()->toDateString(),
                'total_value' => $claim->total_value,
            ]));

        // Call the method
        $result = $allocator->allocate($claim);

        // Assertions
        $this->assertInstanceOf(Batch::class, $result);
        $this->assertEquals($claim->provider_id, $result->provider_id);
        $this->assertEquals($claim->total_value, $result->total_value);
    }

    public function test_it_returns_correct_date_options()
    {
        $this->markTestSkipped();
        $insurer = Insurer::factory()->create(['preferred_date_type' => EncounterDateType::ENCOUNTER_DATE->value]);
        $claim = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'encounter_date' => '2024-04-10',
            'submission_date' => '2024-04-11',
        ]);
        $claim->setRelation('insurer', $insurer); // important for $claim->insurer

        $result = $this->invokeMethod($this->allocator, 'getBatchDateOptions', [$claim]);

        $this->assertEquals([
            ['2024-04-10', EncounterDateType::ENCOUNTER_DATE->value],
            ['2024-04-11', EncounterDateType::SUBMISSION_DATE->value],
        ], $result);
    }

    public function test_it_creates_or_fetches_existing_batch_candidate()
    {
        $this->markTestSkipped();
        $claim = Claim::factory()->make(['insurer_id' => 1, 'provider_id' => 2]);

        $batch = $this->invokeMethod($this->allocator, 'makeBatchCandidate', [
            $claim, '2024-04-10', EncounterDateType::SUBMISSION_DATE,
        ]);

        $this->assertInstanceOf(Batch::class, $batch);
        $this->assertEquals('2024-04-10', $batch->date);
        $this->assertEquals(EncounterDateType::SUBMISSION_DATE, $batch->preferred_date_type);
    }

    public function test_it_checks_if_batch_is_valid_based_on_size_limits()
    {
        $insurer = Insurer::factory()->make([
            'min_batch_size' => 100,
            'max_batch_size' => 500,
        ]);

        $claim = Claim::factory()->make(['total_value' => 200]);
        $claim->setRelation('insurer', $insurer);

        $batch = new Batch(['total_value' => 250]);

        $isValid = $this->invokeMethod($this->allocator, 'isBatchValid', [$claim, $batch]);

        $this->assertTrue($isValid);

        // Test invalid
        $batch->total_value = 400;
        $claim->total_value = 200;

        $this->assertFalse($this->invokeMethod($this->allocator, 'isBatchValid', [$claim, $batch]));
    }

    public function test_it_checks_capacity_is_available()
    {
        $insurer = Insurer::factory()->create([
            'daily_capacity' => 1000,
        ]);

        $claim = Claim::factory()->make(['total_value' => 100]);
        $claim->setRelation('insurer', $insurer);

        $batch = Batch::factory()->make(['date' => Carbon::today()->toDateString()]);

        DailyCapacity::create([
            'insurer_id' => $insurer->id,
            'processing_date' => Carbon::today()->addDay()->toDateString(),
            'used_capacity' => 800,
        ]);

        $hasCapacity = $this->invokeMethod($this->allocator, 'hasCapacity', [$claim, $batch]);

        $this->assertTrue($hasCapacity);

        // Over the limit test
        DailyCapacity::where('insurer_id', $insurer->id)->update(['used_capacity' => 950]);

        $this->assertFalse($this->invokeMethod($this->allocator, 'hasCapacity', [$claim, $batch]));
    }

    public function test_it_allocates_in_future_correctly()
    {
        $this->markTestSkipped();
        $insurer = Insurer::factory()->create([
            'preferred_date_type' => EncounterDateType::ENCOUNTER_DATE->value,
            'min_batch_size' => 100,
            'daily_capacity' => 1000,
        ]);

        $claim = Claim::factory()->make([
            'total_value' => 200,
            'encounter_date' => Carbon::today()->toDateString(),
            'submission_date' => Carbon::today()->toDateString(),
            'provider_id' => Provider::factory(),
        ]);
        $claim->setRelation('insurer', $insurer);

        $result = $this->invokeMethod($this->allocator, 'allocateInFuture', [$claim]);

        $this->assertInstanceOf(Batch::class, $result);
        $this->assertEquals($claim->provider_id, $result->provider_id);
        $this->assertEquals($claim->total_value, $result->total_value);
    }

    // Helper to call protected/private methods

    /**
     * @throws \ReflectionException
     */
    protected function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $refMethod = new \ReflectionMethod($object, $methodName);
        $refMethod->setAccessible(true);

        return $refMethod->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
