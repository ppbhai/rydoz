<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_rides', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_rides', 'actual_scooter_on_seconds')) {
                $table->unsignedInteger('actual_scooter_on_seconds')->nullable()->after('complete_battery_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_rides', function (Blueprint $table) {
            if (Schema::hasColumn('booking_rides', 'actual_scooter_on_seconds')) {
                $table->dropColumn('actual_scooter_on_seconds');
            }
        });
    }
};
