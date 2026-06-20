<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branch_vehicles')) {
            Schema::create('branch_vehicles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('price', 10, 2)->default(0);
                $table->unsignedInteger('time')->default(1);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('vehicles') && Schema::hasTable('branches')) {
            $branch = DB::table('branches')->orderBy('id')->first();

            if ($branch && !DB::table('branch_vehicles')->exists()) {
                $vehicles = DB::table('vehicles')->get();

                foreach ($vehicles as $vehicle) {
                    DB::table('branch_vehicles')->insert([
                        'branch_id' => $branch->id,
                        'name' => $vehicle->name,
                        'quantity' => 1,
                        'price' => $vehicle->price,
                        'time' => $vehicle->time,
                        'created_at' => $vehicle->created_at ?? now(),
                        'updated_at' => $vehicle->updated_at ?? now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_vehicles');
    }
};
