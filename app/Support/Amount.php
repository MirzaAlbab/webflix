<?php

namespace App\Support;

use InvalidArgumentException;

class Amount
{
    public static function minorUnits(mixed $amount): int
    {
        if (! is_scalar($amount) || ! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', (string) $amount)) {
            throw new InvalidArgumentException('Invalid payment amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '');

        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    public static function rupiah(mixed $amount): int
    {
        return intdiv(self::minorUnits($amount) + 50, 100);
    }
}
