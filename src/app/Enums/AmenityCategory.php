<?php

namespace App\Enums;

enum AmenityCategory: string
{
    case ROOM = 'ROOM';
    case BATHROOM = 'BATHROOM';
    case FOOD = 'FOOD';
    case ENTERTAINMENT = 'ENTERTAINMENT';
    case CONNECTIVITY = 'CONNECTIVITY';
    case COMFORT = 'COMFORT';
    case SAFETY = 'SAFETY';
    case OTHER = 'OTHER';
}
