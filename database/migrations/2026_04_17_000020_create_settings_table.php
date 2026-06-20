<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('scanner')->default(false);
                $table->boolean('photo')->default(false);
                $table->unsignedInteger('buffer_time')->default(0);
                $table->timestamps();
            });
        }

        if (\Illuminate\Support\Facades\DB::table('settings')->count() === 0) {
            \Illuminate\Support\Facades\DB::table('settings')->insert([
                'scanner' => 0,
                'photo' => 0,
                'buffer_time' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
