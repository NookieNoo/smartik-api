<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Favorite extends Model
{
	use SoftDeletes;

	public $timestamps = false;

	public function model (): MorphTo {
		return $this->morphTo();
	}

	public function user (): MorphTo {
		return $this->morphTo();
	}
}