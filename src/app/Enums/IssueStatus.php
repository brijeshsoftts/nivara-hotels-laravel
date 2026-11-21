<?php

namespace App\Enums;

enum IssueStatus: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case RESOLVED = 'RESOLVED';
    case CANCELLED = 'CANCELLED';
}
