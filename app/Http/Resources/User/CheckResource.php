<?php

namespace App\Http\Resources\User;

use App\Enums\CartProductStatus;
use App\Services\Showcase\CartService;
use App\Services\ShowcaseService;
use Illuminate\Http\Resources\Json\JsonResource;


class CheckResource extends JsonResource
{
    public function toArray ($request)
    {

        return [
            'type'       => $this->type,
            'url'        => $this->result?->ofd_url,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
