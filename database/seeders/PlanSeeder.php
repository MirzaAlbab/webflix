<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('plans')->truncate();

        $plans = [
            [
                'title' => 'Basic Plan',
                'price' => 9.99,
                'duration' => 1, // 1 month
                'resolution' => '720p',
                'max_devices' => 1,
            ],
            [
                'title' => 'Standard Plan',
                'price' => 14.99,
                'duration' => 1, // 1 month
                'resolution' => '1080p',
                'max_devices' => 2,
            ],
            [
                'title' => 'Premium Plan',
                'price' => 19.99,
                'duration' => 1, // 1 month
                'resolution' => '4K',
                'max_devices' => 4,
            ],
        ];

        DB::table('plans')->insert($plans);
        Schema::enableForeignKeyConstraints();
    }
}
