<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('insurer_id');
            $table->string('batch_identifier')->nullable();
            $table->integer('claim_count')->default(0);
            $table->date('processing_date');
            $table->string('preferred_date_type')->default(\App\Enum\EncounterDateType::SUBMISSION_DATE->value);
            $table->decimal('total_value', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('provider_id')
                ->references('id')
                ->on('providers')->cascadeOnDelete();

            $table->foreign('insurer_id')
                ->references('id')
                ->on('insurers')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
