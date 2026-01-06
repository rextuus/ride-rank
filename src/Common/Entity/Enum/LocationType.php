<?php

namespace App\Common\Entity\Enum;

enum LocationType: string
{
    case AMUSEMENT_PARK = 'amusement_park';
    case CITY = 'city';
    case STATE = 'state';
    case COUNTRY = 'country';
    case MANUFACTURER = 'manufacturer';
    case MODEL = 'model';
    case NOT_DETERMINED = 'not_determined';

    public static function random(): LocationType
    {
        $cases = array_values(self::cases());
        // avoid not determined
        $cases = array_filter($cases, fn($case) => $case !== self::NOT_DETERMINED || $case !== self::COUNTRY);

        return $cases[array_rand($cases)];
    }
}
