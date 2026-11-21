<?php

namespace App\Enums;

enum BedType: string
{
    case SINGLE = 'SINGLE';
    case TWIN = 'TWIN';
    case DOUBLE = 'DOUBLE';
    case QUEEN = 'QUEEN';
    case KING = 'KING';
    case BUNK = 'BUNK';
    case SOFA_BED = 'SOFA_BED';
}
