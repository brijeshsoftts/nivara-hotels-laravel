<?php

namespace App\Enums;

enum Category: string
{
    case RECEPTIONIST = 'RECEPTIONIST';
    case HOUSEKEEPER = 'HOUSEKEEPER';
    case SECURITY_GUARD = 'SECURITY_GUARD';
    case WAITER = 'WAITER';
}
