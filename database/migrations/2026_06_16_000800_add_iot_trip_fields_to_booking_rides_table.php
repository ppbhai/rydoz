<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('booking_rides')) {
            return;
        }

        Schema::table('booking_rides', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_rides', 'trip_distance_km')) {
                $table->decimal('trip_distance_km', 10, 3)->nullable()->after('actual_minutes');
            }

            if (!Schema::hasColumn('booking_rides', 'average_speed_kph')) {
                $table->decimal('average_speed_kph', 10, 2)->nullable()->after('trip_distance_km');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('booking_rides')) {
            return;
        }

        Schema::table('booking_rides', function (Blueprint $table) {
            foreach (['average_speed_kph', 'trip_distance_km'] as $column) {
                if (Schema::hasColumn('booking_rides', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
