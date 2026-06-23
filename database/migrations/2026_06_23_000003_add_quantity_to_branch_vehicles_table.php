<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branch_vehicles') || Schema::hasColumn('branch_vehicles', 'quantity')) {
            return;
        }

        Schema::table('branch_vehicles', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('name');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('branch_vehicles') || !Schema::hasColumn('branch_vehicles', 'quantity')) {
            return;
        }

        Schema::table('branch_vehicles', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
