<?php

namespace App\Services\KKM\Enums;

enum KKMMode: string
{
    case PRINT           = 'print';
    case EMAIL           = 'email';
    case PRINT_AND_EMAIL = 'print_email';
}