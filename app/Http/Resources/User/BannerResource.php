<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;


class BannerResource extends JsonResource
{
    public function toArray ($request)
    {
        return $this->only([
            'title',
            'subtitle',
            'background_color',
            'text_color',
            'type',
            'model_uuid',
            'location',
            'uuid'
        ]);
    }
}
