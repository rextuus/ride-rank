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
}
