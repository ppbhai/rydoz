<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branches')) {
            return;
        }

        $defaultBranchId = $this->defaultBranchId();

        if ($defaultBranchId && Schema::hasTable('users')) {
            DB::table('users')
                ->whereNull('branch_id')
                ->update([
                    'branch_id' => $defaultBranchId,
                    'branch' => DB::raw("COALESCE(NULLIF(branch, ''), 'Default Branch')"),
                ]);
        }

        if ($defaultBranchId && Schema::hasTable('bookings')) {
            $bookingUpdate = [
                'branch_id' => $defaultBranchId,
            ];

            if (Schema::hasColumn('bookings', 'branch')) {
                $bookingUpdate['branch'] = DB::raw("COALESCE(NULLIF(branch, ''), 'Default Branch')");
            }

            if (Schema::hasColumn('bookings', 'branch_name')) {
                $bookingUpdate['branch_name'] = Schema::hasColumn('bookings', 'branch')
                    ? DB::raw("COALESCE(NULLIF(branch_name, ''), NULLIF(branch, ''), 'Default Branch')")
                    : DB::raw("COALESCE(NULLIF(branch_name, ''), 'Default Branch')");
            }

            DB::table('bookings')
                ->whereNull('branch_id')
                ->update($bookingUpdate);
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('branch_id', 'users_branch_id_index');
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->index('branch_id', 'bookings_branch_id_index');
                $table->index(['branch_id', 'status'], 'bookings_branch_status_index');
                $table->index('paid_at', 'bookings_paid_at_index');
            });
        }

        if (Schema::hasTable('booking_rides')) {
            Schema::table('booking_rides', function (Blueprint $table) {
                $table->index('branch_vehicle_id', 'booking_rides_branch_vehicle_id_index');
                $table->index(['booking_id', 'status'], 'booking_rides_booking_status_index');
            });
        }

        if ($defaultBranchId && Schema::hasTable('users') && DB::table('users')->count() === 0) {
            DB::table('users')->insert([
                'name' => 'Default Operator',
                'email' => 'operator@rydoz.local',
                'phone_no' => '9999999999',
                'branch' => 'Default Branch',
                'branch_id' => $defaultBranchId,
                'password' => Hash::make('operator123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('booking_rides')) {
            Schema::table('booking_rides', function (Blueprint $table) {
                $table->dropIndex('booking_rides_branch_vehicle_id_index');
                $table->dropIndex('booking_rides_booking_status_index');
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropIndex('bookings_branch_id_index');
                $table->dropIndex('bookings_branch_status_index');
                $table->dropIndex('bookings_paid_at_index');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_branch_id_index');
            });
        }
    }

    protected function defaultBranchId(): ?int
    {
        $defaultBranch = DB::table('branches')
            ->orderByRaw("CASE WHEN name = 'Default Branch' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        return $defaultBranch?->id ? (int) $defaultBranch->id : null;
    }
};
