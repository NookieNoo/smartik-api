<?php

namespace App\Services\KKM\Enums;

enum KKMTaxSystem: string
{
    case OSN    = 'osn';
    case USN_6  = 'usn6';
    case USN_15 = 'usn15';
    case ESHN   = 'eshn';
    case PATENT = 'patent';
}