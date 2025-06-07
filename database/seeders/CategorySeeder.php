<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('categories')->truncate();

        $categories = [
            'Action',
            'Adventure',
            'Animation',
            'Comedy',
            'Crime',
            'Documentary',
            'Drama',
            'Fantasy',
            'Horror',
            'Musical',
            'Mystery',
            'Romance',
            'Science Fiction',
            'Thriller',
            'Western',
        ];

        foreach ($categories as $category) {
            \App\Models\Category::create([
                'title' => $category,
                'slug' => Str::of($category)->slug('-'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        Schema::enableForeignKeyConstraints();
    }
}
