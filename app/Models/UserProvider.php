<?php

namespace App\Models;

use App\Casts\ProviderExtraCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProvider extends Model
{
	protected $casts = [
		'extra' => ProviderExtraCast::class
	];

	public function user (): BelongsTo {
		return $this->belongsTo(User::class);
	}
}