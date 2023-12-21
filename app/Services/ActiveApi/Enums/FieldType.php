<?php

namespace App\Services\ActiveApi\Enums;

use App\Models\ApiUser;
use App\Models\Driver;
use App\Models\User;

enum FieldType: string
{
	case STRING = 'string';
	case BOOLEAN = 'boolean';
	case NUMBERIC = 'numeric';
	case ARRAY = 'array';
	case JSON = 'json';
	case FILE = 'file';
	case SELECT = 'select';
}