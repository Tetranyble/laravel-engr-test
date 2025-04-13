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
        Schema::create('daily_capacities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('insurer_id');
            $table->date('processing_date');
            $table->decimal('used_capacity', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('insurer_id')
                ->references('id')
                ->on('insurers')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_capacities');
    }
};
