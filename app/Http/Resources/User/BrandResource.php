<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;


class BrandResource extends JsonResource
{
    public function toArray ($request)
    {
        return $this->only([
            'name',
            'slug',
            'color',
            'background',
            'position'
        ]);
    }
}
