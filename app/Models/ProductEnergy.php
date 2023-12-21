<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductEnergy extends Model
{
	public $timestamps = false;

	protected $casts = [
		'calories' => 'float',
		'protein'  => 'float',
		'fat'      => 'float',
		'carbon'   => 'float'
	];

	public function product (): BelongsTo {
		return $this->belongsTo(Product::class);
	}
}