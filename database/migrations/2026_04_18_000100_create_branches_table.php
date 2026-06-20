<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->unsignedInteger('buffer_time')->default(0);
                $table->boolean('photo_enabled')->default(false);
                $table->boolean('scanner_enabled')->default(false);
                $table->boolean('vehicle_number_required')->default(false);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('settings')) {
            $settings = DB::table('settings')->first();

            if ($settings && !DB::table('branches')->exists()) {
                DB::table('branches')->insert([
                    'name' => 'Default Branch',
                    'buffer_time' => (int) ($settings->buffer_time ?? 0),
                    'photo_enabled' => (int) ($settings->photo ?? 0),
                    'scanner_enabled' => (int) ($settings->scanner ?? 0),
                    'vehicle_number_required' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
