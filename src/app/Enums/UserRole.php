<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'ADMIN';
    case MANAGER = 'MANAGER';
    case STAFF = 'STAFF';
    case CUSTOMER = 'CUSTOMER';
}
