<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Membership;
use App\Models\Movie;
use App\Models\Plan;
use App\Models\Rating;
use App\Models\User;
use App\Support\DemoMode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! DemoMode::enabled()) {
            throw new RuntimeException('Enable WEBFLIX_DEMO in a local/testing sandbox environment before seeding the demo.');
        }

        DB::transaction(function () {
            $plans = [];
            foreach ([['Basic', 49999, '720p', 1], ['Standard', 89999, '1080p', 2], ['Premium', 129999, '4K', 4]] as [$title, $price, $resolution, $devices]) {
                $plans[$title] = Plan::firstOrCreate(['title' => $title], [
                    'price' => $price, 'resolution' => $resolution, 'duration' => 30, 'max_devices' => $devices,
                ]);
            }

            $users = [];
            foreach (['viewer' => 'Alex Morgan', 'new' => 'Jordan Lee', 'expired' => 'Riley Taylor'] as $account => $name) {
                $users[$account] = User::firstOrCreate(['email' => $account.'@webflix.example'], [
                    'name' => $name, 'password' => 'demo-password', 'email_verified_at' => now(),
                ]);
            }

            foreach (['viewer', 'expired'] as $account) {
                Membership::firstOrCreate([
                    'user_id' => $users[$account]->id, 'plan_id' => $plans['Standard']->id, 'transaction_id' => null,
                ], [
                    'start_date' => now()->subDays(2),
                    'end_date' => $account === 'viewer' ? now()->addDays(30) : now()->subDay(),
                    'active' => $account === 'viewer',
                ]);
            }

            $category = Category::firstOrCreate(['slug' => 'demo-collection'], ['title' => 'Demo collection']);
            foreach (['Night Signal', 'Orbit Nine', 'The Last Crossing', 'City of Echoes', 'Paper Moons', 'Beyond the Tides', 'Wild Circuit', 'A Quiet Horizon'] as $index => $title) {
                $slug = str($title)->slug()->toString();
                $movie = Movie::firstOrCreate(['slug' => $slug], [
                    'title' => $title,
                    'description' => 'A fictional story from the Webflix demo collection. Open the preview to explore membership access and plan resolution.',
                    'director' => 'Demo Studio', 'writers' => 'Demo Writers', 'stars' => 'Demo Cast',
                    'poster' => '/assets/demo/'.$slug.'.svg', 'release_date' => '2026-01-15', 'duration' => 90 + $index * 5,
                    'url_720' => '/demo/player?quality=720p', 'url_1080' => '/demo/player?quality=1080p', 'url_4k' => '/demo/player?quality=4K',
                ]);
                $movie->categories()->syncWithoutDetaching([$category->id]);
                Rating::firstOrCreate(['user_id' => $users['viewer']->id, 'movie_id' => $movie->id], ['rating' => 8 + $index / 10]);
            }
        });
    }
}
