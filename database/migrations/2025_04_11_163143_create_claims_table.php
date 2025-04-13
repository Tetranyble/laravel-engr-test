<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('insurer_id');
            $table->string('specialty');
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->date('encounter_date');
            $table->dateTime('submission_date')->useCurrent();
            $table->integer('priority_level')->unsigned();
            $table->decimal('total_value', 12, 2);
            $table->timestamps();

            $table->foreign('provider_id')
                ->references('id')
                ->on('providers')->cascadeOnDelete();

            $table->foreign('insurer_id')
                ->references('id')
                ->on('insurers')->cascadeOnDelete();

            $table->foreign('batch_id')
                ->references('id')
                ->on('batches')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
