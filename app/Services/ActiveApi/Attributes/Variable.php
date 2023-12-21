<?php

namespace App\Services\ActiveApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Variable
{
	public function __construct (
		public string  $slug,
		public ?string $name = null,
		public ?string $type = null,
		public ?string $description = null,
		public ?string $response = null,
	) {
	}
}