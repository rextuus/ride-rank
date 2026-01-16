<?php

namespace App\Service\Player;

enum IsoToLocationIdent: string
{
    case Germany = 'DE';
    case France = 'FR';
    case UnitedStates = 'US';
    case Italy = 'IT';
    case Spain = 'ES';

    public static function getIdentByIso(string $isoCode): ?string
    {
        foreach (self::cases() as $case) {
            if ($case->value === $isoCode) {
                return $case->name; // this is the string your DB uses
            }
        }

        return null; // not found
    }
}
