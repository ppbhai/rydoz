<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blacklist_reasons')) {
            return;
        }

        Schema::create('blacklist_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('reason');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blacklist_reasons');
    }
};
