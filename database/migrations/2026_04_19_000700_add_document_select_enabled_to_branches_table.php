<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'document_select_enabled')) {
                $table->boolean('document_select_enabled')->default(false)->after('vehicle_number_required');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'document_select_enabled')) {
                $table->dropColumn('document_select_enabled');
            }
        });
    }
};
