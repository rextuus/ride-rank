<?php

namespace App\Service;

class UnitConversionService
{
    public const FEET_TO_METERS = 0.3048;
    public const MPH_TO_KMH = 1.60934;

    public function feetToMeters(float $feet): float
    {
        return $feet * self::FEET_TO_METERS;
    }

    public function meterToFeet(float $meters): float
    {
        return $meters / self::FEET_TO_METERS;
    }

    public function mphToKmh(float $mph): float
    {
        return $mph * self::MPH_TO_KMH;
    }

    public function kmhToMph(float $kmh): float
    {
        return $kmh / self::MPH_TO_KMH;
    }
}
