<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'phone_no')) {
                    $table->string('phone_no')->nullable()->after('email');
                }

                if (!Schema::hasColumn('users', 'branch')) {
                    $table->string('branch')->nullable()->after('phone_no');
                }

                if (!Schema::hasColumn('users', 'branch_id')) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('branch');
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('bookings', 'branch')) {
                    $table->string('branch')->nullable()->after('paid_at');
                }

                if (!Schema::hasColumn('bookings', 'branch_id')) {
                    $table->unsignedBigInteger('branch_id')->nullable()->after('branch');
                }

                if (!Schema::hasColumn('bookings', 'branch_name')) {
                    $table->string('branch_name')->nullable()->after('branch_id');
                }
            });
        }

        if (Schema::hasTable('booking_rides')) {
            Schema::table('booking_rides', function (Blueprint $table) {
                if (!Schema::hasColumn('booking_rides', 'branch_vehicle_id')) {
                    $table->unsignedBigInteger('branch_vehicle_id')->nullable()->after('vehicle_id');
                }
            });
        }

        if (!Schema::hasTable('branches')) {
            return;
        }

        $branchMap = DB::table('branches')->pluck('id', 'name');

        if ($branchMap->isEmpty()) {
            return;
        }

        if (Schema::hasTable('users')) {
            $users = DB::table('users')->select('id', 'branch', 'branch_id')->get();

            foreach ($users as $user) {
                if ($user->branch_id || !$user->branch || !$branchMap->has($user->branch)) {
                    continue;
                }

                DB::table('users')->where('id', $user->id)->update([
                    'branch_id' => $branchMap[$user->branch],
                ]);
            }
        }

        if (Schema::hasTable('bookings')) {
            $bookings = DB::table('bookings')->select('id', 'branch', 'branch_id', 'branch_name')->get();

            foreach ($bookings as $booking) {
                $branchId = $booking->branch_id;
                $branchName = $booking->branch_name ?: $booking->branch;

                if (!$branchId && $booking->branch && $branchMap->has($booking->branch)) {
                    $branchId = $branchMap[$booking->branch];
                }

                DB::table('bookings')->where('id', $booking->id)->update([
                    'branch_id' => $branchId,
                    'branch_name' => $branchName,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('booking_rides') && Schema::hasColumn('booking_rides', 'branch_vehicle_id')) {
            Schema::table('booking_rides', function (Blueprint $table) {
                $table->dropColumn('branch_vehicle_id');
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'branch')) {
                    $table->dropColumn('branch');
                }

                if (Schema::hasColumn('bookings', 'branch_name')) {
                    $table->dropColumn('branch_name');
                }

                if (Schema::hasColumn('bookings', 'branch_id')) {
                    $table->dropColumn('branch_id');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'branch_id')) {
                    $table->dropColumn('branch_id');
                }
            });
        }
    }
};
