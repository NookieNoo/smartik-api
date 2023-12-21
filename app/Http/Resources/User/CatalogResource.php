<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;


class CatalogResource extends JsonResource
{
	public function toArray ($request) {
		return [
            ...$this->only([
                'name',
                'uuid'
            ]),
            'children' => CatalogResource::collection($this->children),
            'media_uuid' => $this->getFirstMedia('icon')?->uuid
        ];
	}
}
