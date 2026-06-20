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
            if (!Schema::hasColumn('booking_rides', 'assign_battery_percent')) {
                $table->unsignedTinyInteger('assign_battery_percent')->nullable()->after('average_speed_kph');
            }

            if (!Schema::hasColumn('booking_rides', 'complete_battery_percent')) {
                $table->unsignedTinyInteger('complete_battery_percent')->nullable()->after('assign_battery_percent');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('booking_rides')) {
            return;
        }

        Schema::table('booking_rides', function (Blueprint $table) {
            foreach (['complete_battery_percent', 'assign_battery_percent'] as $column) {
                if (Schema::hasColumn('booking_rides', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
