<?php

namespace App\Enums;

enum TaskType: string
{
    case CLEANING = 'CLEANING';
    case DEEP_CLEAN = 'DEEP_CLEAN';
    case TURNDOWN = 'TURNDOWN';
}
