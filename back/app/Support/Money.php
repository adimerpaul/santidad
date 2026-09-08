<?php

namespace App\Support;

final class Money
{
    private const CENTS_PER_UNIT = 100;
    private const CENTS_PER_TENTH = 10;

    private function __construct()
    {
    }

    public static function toCents(int|float|string|null $amount): int
    {
        return (int) round(((float) ($amount ?? 0)) * self::CENTS_PER_UNIT, 0, PHP_ROUND_HALF_UP);
    }

    public static function fromCents(int $cents): float
    {
        return $cents / self::CENTS_PER_UNIT;
    }

    public static function roundToCents(int|float|string|null $amount): float
    {
        return self::fromCents(self::toCents($amount));
    }

    public static function roundCentsToTenth(int $cents): int
    {
        $sign = $cents < 0 ? -1 : 1;
        $absolute = abs($cents);
        $rounded = intdiv($absolute + 5, self::CENTS_PER_TENTH) * self::CENTS_PER_TENTH;

        return $rounded * $sign;
    }

    public static function roundToTenth(int|float|string|null $amount): float
    {
        return self::fromCents(self::roundCentsToTenth(self::toCents($amount)));
    }
}
