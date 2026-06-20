<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('booking_rides')) {
            return;
        }

        Schema::table('booking_rides', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_rides', 'discount_reason')) {
                $table->string('discount_reason')->nullable()->after('charge');
            }

            if (!Schema::hasColumn('booking_rides', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_reason');
            }

            if (!Schema::hasColumn('booking_rides', 'final_charge')) {
                $table->decimal('final_charge', 10, 2)->default(0)->after('discount_amount');
            }
        });

        DB::table('booking_rides')->update([
            'final_charge' => DB::raw('charge'),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('booking_rides')) {
            return;
        }

        Schema::table('booking_rides', function (Blueprint $table) {
            foreach (['final_charge', 'discount_amount', 'discount_reason'] as $column) {
                if (Schema::hasColumn('booking_rides', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
