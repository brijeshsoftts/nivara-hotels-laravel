<?php

namespace App\Enums;

enum HousekeepingStatus: string
{
    case CLEAN = 'CLEAN';
    case DIRTY = 'DIRTY';
    case CLEANING = 'CLEANING';
}
