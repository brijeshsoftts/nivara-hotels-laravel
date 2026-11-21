<?php

namespace App\Enums;

enum OccupancyStatus: string
{
    case VACANT = 'VACANT';
    case RESERVED = 'RESERVED';
    case OCCUPIED = 'OCCUPIED';
    case OUT_OF_ORDER = 'OUT_OF_ORDER';
}
