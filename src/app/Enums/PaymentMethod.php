<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'CASH';
    case ONLINE = 'ONLINE';
}
