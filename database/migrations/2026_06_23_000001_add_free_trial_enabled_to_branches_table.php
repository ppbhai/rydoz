<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'free_trial_enabled')) {
                $table->boolean('free_trial_enabled')->default(false)->after('document_select_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'free_trial_enabled')) {
                $table->dropColumn('free_trial_enabled');
            }
        });
    }
};
