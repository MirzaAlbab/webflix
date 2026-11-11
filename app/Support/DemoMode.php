<?php

namespace App\Support;

class DemoMode
{
    public static function simulatesPayments(): bool
    {
        return self::enabled() && config('demo.simulate_payments', true);
    }

    public static function enabled(): bool
    {
        return config('demo.enabled')
            && app()->environment(['local', 'testing'])
            && ! config('midtrans.is_production');
    }
}
