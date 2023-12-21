<?php

namespace App\Traits;

use App\Models\Device;
use App\Models\DeviceHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasDevice
{
	public function device (): MorphOne {
		return $this->morphOne(Device::class, 'user');
	}

	public function devices (): MorphMany {
		return $this->morphMany(DeviceHistory::class, 'user');
	}


}