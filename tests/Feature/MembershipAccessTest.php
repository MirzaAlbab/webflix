<?php

use App\Events\MembershipHasExpired;
use App\Http\Middleware\CheckDeviceLimit;
use App\Jobs\CheckMembershipStatus;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Support\PaymentFixtures;

uses(PaymentFixtures::class);

beforeEach(function () {
    $this->travelTo(now()->setDateTime(2026, 11, 11, 12, 0, 0));
});

it('requires a current membership for movie playback', function (string $state) {
    $user = User::factory()->create();
    $plan = $this->plan();
    $movie = $this->movie();
    if ($state !== 'missing') {
        $this->membership($user, $plan, match ($state) {
            'expired' => ['end_date' => now()->subSecond()],
            'boundary' => ['end_date' => now()],
            'future' => ['start_date' => now()->addDay()],
            'inactive' => ['active' => false],
        });
    }
    expect($user->hasMembershipPlan())->toBeFalse();
    expect($user->getCurrentPlan())->toBeNull();
    $this->actingAs($user)->get(route('movies.show', $movie->slug))->assertRedirect(route('subscribe.plans'));
})->with(['missing', 'expired', 'boundary', 'future', 'inactive']);

it('selects the stream matching the paid plan resolution', function (string $resolution) {
    $user = User::factory()->create();
    $plan = $this->plan(['resolution' => $resolution]);
    $movie = $this->movie();
    $this->membership($user, $plan);
    $this->withoutMiddleware(CheckDeviceLimit::class);
    $this->actingAs($user)->get(route('movies.show', $movie->slug))
        ->assertOk()->assertSee('/demo/player?quality='.$resolution, false);
})->with(['720p', '1080p', '4K']);

it('renders unrated movies without a type error', function () {
    $movie = $this->movie();
    expect($movie->average_rating)->toBe(0.0);
});

it('deactivates memberships at their exact expiry time without skipping batches', function () {
    Event::fake([MembershipHasExpired::class]);
    $user = User::factory()->create();
    $plan = $this->plan();
    $rows = array_fill(0, 205, [
        'user_id' => $user->id, 'plan_id' => $plan->id,
        'start_date' => now()->subDays(30), 'end_date' => now(), 'active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    Membership::insert($rows);
    $future = $this->membership($user, $plan);
    (new CheckMembershipStatus)->handle();

    expect(Membership::where('active', true)->count())->toBe(1);
    expect($future->fresh()->active)->toBeTrue();
    Event::assertDispatchedTimes(MembershipHasExpired::class, 205);
    (new CheckMembershipStatus)->handle();
    Event::assertDispatchedTimes(MembershipHasExpired::class, 205);
});

it('retains future-start memberships and expires intraday memberships', function () {
    Event::fake([MembershipHasExpired::class]);
    $user = User::factory()->create();
    $plan = $this->plan();
    $expired = $this->membership($user, $plan, ['end_date' => now()->subHour()]);
    $future = $this->membership($user, $plan, ['start_date' => now()->addDay()]);
    (new CheckMembershipStatus)->handle();
    expect($expired->fresh()->active)->toBeFalse();
    expect($future->fresh()->active)->toBeTrue();
});
