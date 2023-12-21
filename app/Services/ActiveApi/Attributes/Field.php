<?php

namespace App\Services\ActiveApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Field
{
	public function __construct (
		public string  $name,
		public ?string $title = null,
		public ?string $type = null,
		public ?bool   $required = null,
		public ?bool   $nullable = null,
		public ?array  $validations = null,
		public ?string $description = null,
		public ?array  $samples = null,
		public mixed   $default = null,
		public ?bool   $ignore = null,
		public mixed   $extra = null,
	) {
	}
}