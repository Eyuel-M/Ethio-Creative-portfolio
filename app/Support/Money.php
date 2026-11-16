<?php

namespace App\Support;

/** Money is stored in integer minor units (cents/santim). Never floats. */
class Money
{
    public static function format(?int $minor, string $currency = 'ETB'): string
    {
        if ($minor === null) {
            return '—';
        }
        $major = intdiv($minor, 100);
        $cents = $minor % 100;
        $num = number_format($major).($cents ? '.'.str_pad((string) $cents, 2, '0', STR_PAD_LEFT) : '');

        return $currency === 'USD' ? '$'.$num : $num.' '.$currency;
    }

    public static function toMinor($major): ?int
    {
        if ($major === null || $major === '') {
            return null;
        }

        return (int) round(((float) str_replace(',', '', (string) $major)) * 100);
    }

    public static function toMajor(?int $minor): ?string
    {
        return $minor === null ? null : rtrim(rtrim(number_format($minor / 100, 2, '.', ''), '0'), '.');
    }
}
