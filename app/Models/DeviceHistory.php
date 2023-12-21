<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceHistory extends Model
{
	const UPDATED_AT = false;

	protected $casts = [
		'device' => 'json'
	];
}