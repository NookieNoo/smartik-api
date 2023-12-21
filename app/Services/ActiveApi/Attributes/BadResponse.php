<?php

namespace App\Services\ActiveApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class BadResponse extends Response
{
	public function __construct (
		public          $response = null,
		protected int   $code = 422,
		protected bool  $bad = true,
		protected mixed $construct = []
	) {
	}
}