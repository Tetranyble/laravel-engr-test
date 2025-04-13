<?php

namespace Tests\Feature\Controller;

use App\Models\Batch;
use App\Models\Insurer;
use App\Models\Provider;
use App\Notifications\ClaimBatchNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClaimControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_claim_to_the_insurer()
    {

        $requestPayload = [
            'provider_name' => 'MedCare Clinic',
            'insurer_code' => 'HMO123',
            'encounter_date' => now()->toDateString(),
            'specialty' => 'cardiology',
            'priority_level' => 3,
            'items' => [
                ['name' => 'Consultation', 'unit_price' => 5000, 'quantity' => 1],
                ['name' => 'ECG', 'unit_price' => 8000, 'quantity' => 1],
            ],
        ];

        $insurer = Insurer::factory()->create();
        $provider = Provider::factory()->create();

        $requestPayload['insurer_code'] = $insurer->code;
        $requestPayload['provider_name'] = $provider->name;
        $response = $this->postJson(route('v1.claims.store'), $requestPayload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'insurer_id',
                'provider_id',
                'total_value',
            ]);

        $totalValue = collect($requestPayload['items'])->sum(function ($item) {
            return $item['unit_price'] * $item['quantity'];
        });
        $this->assertDatabaseHas('claims', [
            'insurer_id' => $insurer->id,
            'total_value' => $totalValue,
        ]);

        $this->assertDatabaseHas('batches', [
            'insurer_id' => $insurer->id,
        ]);

    }

    public function test_it_creates_a_claim_and_sends_email_to_the_insurer()
    {

        Notification::fake();
        $requestPayload = [
            'provider_name' => 'MedCare Clinic',
            'insurer_code' => 'HMO123',
            'encounter_date' => now()->toDateString(),
            'specialty' => 'cardiology',
            'priority_level' => 3,
            'items' => [
                ['name' => 'Consultation', 'unit_price' => 5000, 'quantity' => 1],
                ['name' => 'ECG', 'unit_price' => 8000, 'quantity' => 1],
            ],
        ];

        $insurer = Insurer::factory()->create();
        $provider = Provider::factory()->create();

        $requestPayload['insurer_code'] = $insurer->code;
        $requestPayload['provider_name'] = $provider->name;
        $response = $this->postJson(route('v1.claims.store'), $requestPayload);

        $batch = $insurer->batches()->first();

        Notification::assertSentTo(
            $batch,
            ClaimBatchNotification::class
        );

        //        $this->assertDatabaseHas('notifications', [
        //            'notifiable_type' => Batch::class,
        //            'notifiable_id' => $batch->id,
        //            'type' => ClaimBatchNotification::class,
        //        ]);

    }

    public function test_it_handles_invalid_data_gracefully()
    {
        // Arrange: Invalid request payload (e.g., missing required fields)
        $requestPayload = [
            'insurer_code' => null,  // Invalid data
            'provider_id' => 2,
            'total_value' => 1000,
        ];

        // Act: Make the API request
        $response = $this->postJson(route('v1.claims.store'), $requestPayload);

        // Assert: Validate that validation errors are returned
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['insurer_code']);
    }
}
