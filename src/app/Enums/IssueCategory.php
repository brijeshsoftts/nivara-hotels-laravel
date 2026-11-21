<?php

namespace App\Enums;

enum IssueCategory: string
{
    case PLUMBING = 'PLUMBING';
    case ELECTRICAL = 'ELECTRICAL';
    case HVAC = 'HVAC';
    case FURNITURE = 'FURNITURE';
    case APPLIANCE = 'APPLIANCE';
    case INTERNET = 'INTERNET';
    case BATHROOM = 'BATHROOM';
    case DOOR_LOCK = 'DOOR_LOCK';
    case LIGHTING = 'LIGHTING';
    case OTHER = 'OTHER';
}
