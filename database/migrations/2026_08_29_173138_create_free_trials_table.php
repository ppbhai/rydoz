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
        if (Schema::hasTable('free_trials')) {
            return;
        }

        Schema::create('free_trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('branch_vehicle_id')->nullable()->constrained('branch_vehicles')->nullOnDelete();
            $table->string('vehicle_name')->nullable();
            $table->string('scooter_id');
            $table->timestamp('assigned_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->decimal('distance_km', 8, 3)->nullable();
            $table->unsignedTinyInteger('battery_percent_start')->nullable();
            $table->unsignedTinyInteger('battery_percent_end')->nullable();
            $table->string('status')->default('ongoing');
            $table->timestamps();

            $table->index(['branch_id', 'assigned_at']);
            $table->index(['scooter_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('free_trials');
    }
};
