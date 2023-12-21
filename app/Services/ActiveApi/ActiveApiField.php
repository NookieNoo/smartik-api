<?php

namespace App\Services\ActiveApi;

use App\Services\ActiveApi\Attributes\Field;
use App\Services\ActiveApi\Enums\FieldType;

class ActiveApiField
{
	public function __construct (
		private string  $from,
		public string   $slug,
		private ?string $type = null,
		private ?bool   $required = null,
		private ?bool   $nullable = null,
		private ?string $title = null,
		private ?string $description = null,
		private mixed   $default = null,
		private ?array  $samples = [],
		private ?array  $validations = [],
		private ?bool   $ignore = false,
		private mixed   $extra = null,
	) {
		$this->title = $title ?? $slug;

		foreach ($validations as $k => $validation) {
			if (!is_string($validation) && method_exists($validation, '__toString')) {
				$validations[$k] = (string)$validation;
			}
		}
		$this->validations = $validations;

		$this->required = $required ?? in_array('required', $this->validations);
		$this->nullable = $nullable ?? in_array('nullable', $this->validations);
		$this->type = $type ?? static::typeFromValidations($this->validations);
	}

	public static function parseRule (string $rule, array $validations): self {
		return new static(
			from: 'rule',
			slug: $rule,
			validations: $validations,
		);
	}

	public static function parseField (Field $field): self {
		return new static(
			from: 'field',
			slug: $field->name,
			type: $field->type,
			required: $field->required ?? false,
			nullable: $field->nullable !== null ? $field->nullable : true,
			title: $field->title,
			description: $field->description,
			default: $field->default,
			samples: $field->samples ?? [],
			validations: $field->validations ?? [],
			ignore: $field->ignore,
			extra: $field->extra,
		);
	}

	public static function typeFromValidations (array|null $validations): string {
		if ($validations === null) return FieldType::STRING->value;
		return match (static::primitive($validations)) {
			'accepted',
			'accepted_if',
			'boolean',
			'declined',
			'declined_if' => FieldType::BOOLEAN->value,

			'alpha_num',
			'integer',
			'numeric',
			'digits',
			'digits_between',
			'between'     => FieldType::NUMBERIC->value,

			'array'       => FieldType::ARRAY->value,

			'file',
			'image'       => FieldType::FILE->value,

			'json'        => FieldType::JSON->value,

			default       => FieldType::STRING->value
		};
	}

	private static function primitive ($validations) {
		$primitives = [
			'exists',
			'accepted',
			'accepted_if',
			'boolean',
			'declined',
			'declined_if',
			'alpha_num',
			'integer',
			'numeric',
			'digits',
			'digits_between',
			'between',
			'array',
			'file',
			'image',
			'json',

		];

		foreach ($validations as $validation) {
			foreach ($primitives as $primitive) {
				if (str_starts_with($validation, $primitive)) {
					return $primitive;
				}
			}
		}

		return false;
	}

	public function update (array|Field $data) {
		if ($data instanceof Field) {
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
			'type'        => $this->type,
			'required'    => $this->required,
			'nullable'    => $this->nullable,
			'title'       => $this->title,
			'description' => $this->description,
			'default'     => $this->default,
			'samples'     => $this->samples,
			'validations' => $this->validations,
			'ignore'      => $this->ignore,
			'extra'       => $this->extra,
		];
	}
}