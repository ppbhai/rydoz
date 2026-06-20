<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('booking_rides')) {
            return;
        }

        Schema::create('booking_rides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('vehicle_name');
            $table->decimal('vehicle_price', 10, 2)->default(0);
            $table->integer('vehicle_time')->default(0);
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('requested_minutes')->default(0);
            $table->string('ride_number')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->unsignedInteger('actual_minutes')->nullable();
            $table->decimal('charge', 10, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_rides');
    }
};
