<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->index();
            $table->string('email');
            $table->string('preferred_date_type')->default(\App\Enum\EncounterDateType::ENCOUNTER_DATE->value);
            $table->json('specialty_multipliers'); // {specialty: multiplier} e.g. {"cardiology": 1.2}
            $table->json('priority_multipliers'); // {priority: multiplier} e.g. {"1": 1.0, "5": 1.5}
            $table->integer('daily_capacity');
            $table->integer('min_batch_size');
            $table->integer('max_batch_size');
            $table->decimal('month_min_percent_limit', 12, 2);
            $table->decimal('month_max_percent_limit', 12, 2);
            $table->decimal('base_processing_cost', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurers');
    }
};
