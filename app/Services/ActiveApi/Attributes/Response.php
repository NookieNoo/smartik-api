<?php

namespace App\Services\ActiveApi\Attributes;

use App\Exceptions\ApiException;
use App\Exceptions\Handler;
use Attribute;
use Illuminate\Container\Container;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Response
{
	public function __construct (
		public          $response = null,
		protected int   $code = 200,
		protected bool  $bad = false,
		protected mixed $construct = []
	) {
		if (!is_array($construct)) {
			$this->construct = [$construct];
		}
	}

	public function execute () {
		if (is_a($this->response, ApiException::class, true)) {
			$instance = (new \ReflectionClass($this->response))->newInstanceArgs($this->construct);
			$handler = Handler::apiRender($instance);
			return [
				'response' => $handler,
				'code'     => $instance->getCode(),
			];
		}
		return [
			'response' => $this->response,
			'code'     => $this->code
		];
	}
}