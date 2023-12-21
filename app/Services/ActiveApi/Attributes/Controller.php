<?php

namespace App\Services\ActiveApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Controller
{
	public function __construct (
		private ?string $title = null,
		private ?string $slug = null,
	) {
	}
}