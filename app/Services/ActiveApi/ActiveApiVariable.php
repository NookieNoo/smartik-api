<?php

namespace App\Services\ActiveApi;

use App\Services\ActiveApi\Attributes\Field;
use App\Services\ActiveApi\Attributes\Variable;
use App\Services\ActiveApi\Enums\FieldType;

class ActiveApiVariable
{
	public function __construct (
		private string  $slug,
		private ?string $title = null,
		private ?string $type = null,
		private ?string $description = null,
		private ?string $response = null,
	) {
		$this->name = $name ?? $slug;
	}

	public static function parseVariable (Variable $variable): self {
		return new static(
			slug: $variable->slug,
			title: $variable->name,
			type: $variable->type,
			description: $variable->description,
			response: $variable->response,
		);
	}

	public function update (array|Variable $data) {
		if ($data instanceof Variable) {
			$data = (array)$data;
		}

		foreach ($data as $k => $v) {
			if ($v !== null) {
				$this->{$k} = $v;
			}
		}
	}

	public function toArray () {
		return [
			'slug'        => $this->slug,
			'title'       => $this->title,
			'type'        => $this->type,
			'description' => $this->description,
			'response'    => '@' . $this->response
		];
	}
}