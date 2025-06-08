<?php

namespace App\Jobs;

use App\Models\Membership;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckMembershipStatus implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Membership::where('active', true)
            ->where('end_date', '<' ,now()->toDateTimeString())
            ->chunk(100)->each(function ($memberships) {
                foreach ($memberships as $membership) {
                    $membership->update([
                        'active' => false,
                    ]);
                }
            });
    }
}
