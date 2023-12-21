<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSettings extends Model
{
	protected $casts = [
		'value' => 'json'
	];

	public function user () {
		return $this->morphTo();
	}
}