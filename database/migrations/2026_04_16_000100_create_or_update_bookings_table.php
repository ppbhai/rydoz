<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('mobile');
                $table->string('otp')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->text('vehicles')->nullable();
                $table->string('document_type')->nullable();
                $table->string('front_image')->nullable();
                $table->string('back_image')->nullable();
                $table->string('status')->default('otp_pending');
                $table->string('discount_reason')->nullable();
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->decimal('final_amount', 10, 2)->default(0);
                $table->string('payment_method')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('branch')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'document_type')) {
                $table->string('document_type')->nullable()->after('vehicles');
            }
            if (!Schema::hasColumn('bookings', 'front_image')) {
                $table->string('front_image')->nullable()->after('document_type');
            }
            if (!Schema::hasColumn('bookings', 'back_image')) {
                $table->string('back_image')->nullable()->after('front_image');
            }
            if (!Schema::hasColumn('bookings', 'status')) {
                $table->string('status')->default('otp_pending')->after('back_image');
            }
            if (!Schema::hasColumn('bookings', 'discount_reason')) {
                $table->string('discount_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('bookings', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_reason');
            }
            if (!Schema::hasColumn('bookings', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('bookings', 'final_amount')) {
                $table->decimal('final_amount', 10, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('bookings', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('final_amount');
            }
            if (!Schema::hasColumn('bookings', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('bookings', 'branch')) {
                $table->string('branch')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            foreach (['paid_at', 'payment_method', 'final_amount', 'total_amount', 'discount_amount', 'discount_reason'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
