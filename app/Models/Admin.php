<?php

namespace App\Models;

use App\Traits\LastActive;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
	use LastActive, SoftDeletes;
}