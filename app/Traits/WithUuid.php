<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait WithUuid
{
	public function creating (Model $model) {
		if (!$model->uuid) {
			$model->uuid = (string)Str::uuid();
		}
	}
}