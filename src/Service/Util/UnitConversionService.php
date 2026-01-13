<?php
namespace App\Service\Util;

class UnitConversionService
{
    public const FEET_TO_METERS = 0.3048;
    public const MPH_TO_KMH = 1.60934;

    public function feetToMeters(float $feet, bool $round = true): float
    {
        $result = $feet * self::FEET_TO_METERS;

        return $round ? round($result) : $result;
    }

    public function meterToFeet(float $meters, bool $round = true): float
    {
        $result = $meters / self::FEET_TO_METERS;

        return $round ? round($result) : $result;
    }

    public function mphToKmh(float $mph, bool $round = true): float
    {
        $result = $mph * self::MPH_TO_KMH;

        return $round ? round($result) : $result;
    }

    public function kmhToMph(float $kmh, bool $round = true): float
    {
        $result = $kmh / self::MPH_TO_KMH;

        return $round ? round($result) : $result;
    }
}
