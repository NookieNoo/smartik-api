<?php

namespace App\Services\ActiveApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Position
{
	public function __construct (
		public int $value = 0,
	) {
	}
}