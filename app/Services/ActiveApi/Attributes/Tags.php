<?php

namespace App\Services\ActiveApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Tags
{
	private $tags;

	public function __construct (
		array|string ...$tags,
	) {
		$result = [];
		foreach ($tags as $tag) {
			if (is_array($tag)) {
				$result = [...$result, ...$tag];
			} else {
				$result = [...$result, $tag];
			}
		}
		$result = [...array_unique($result)];
		$this->tags = $result;
	}
}