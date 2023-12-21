<?php

namespace App\Services\ActiveApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Nested
{
	public function __construct (
		public string|array $name,
	) {
	}
}