<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('unm');
                $table->string('email')->unique();
                $table->string('pwd');
                $table->string('image')->nullable();
                $table->string('token')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('admins')) {
            return;
        }

        if (!Schema::hasColumn('admins', 'image') || !Schema::hasColumn('admins', 'token')) {
            Schema::table('admins', function (Blueprint $table) {
                if (!Schema::hasColumn('admins', 'image')) {
                    $table->string('image')->nullable()->after('pwd');
                }

                if (!Schema::hasColumn('admins', 'token')) {
                    $table->string('token')->nullable()->after('image');
                }
            });
        }

        if (\Illuminate\Support\Facades\DB::table('admins')->count() === 0) {
            \Illuminate\Support\Facades\DB::table('admins')->insert([
                'unm' => 'Administrator',
                'email' => 'admin@rydoz.local',
                'pwd' => Hash::make('admin123'),
                'image' => null,
                'token' => Str::random(32),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
