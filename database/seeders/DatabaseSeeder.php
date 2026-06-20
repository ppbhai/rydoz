<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $setting = Setting::query()->firstOrCreate([], [
            'scanner' => 0,
            'photo' => 0,
            'buffer_time' => 0,
        ]);

        $branch = Branch::query()->firstOrCreate(
            ['name' => 'Default Branch'],
            [
                'buffer_time' => (int) $setting->buffer_time,
                'photo_enabled' => (bool) $setting->photo,
                'scanner_enabled' => (bool) $setting->scanner,
                'vehicle_number_required' => false,
            ]
        );

        Admin::query()->firstOrCreate(
            ['email' => 'admin@rydoz.local'],
            [
                'unm' => 'Administrator',
                'pwd' => Hash::make('admin123'),
                'image' => null,
                'token' => Str::random(32),
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'operator@rydoz.local'],
            [
                'name' => 'Default Operator',
                'phone_no' => '9999999999',
                'branch' => $branch->name,
                'branch_id' => $branch->id,
                'password' => Hash::make('operator123'),
            ]
        );
    }
}
