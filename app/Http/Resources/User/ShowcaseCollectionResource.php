<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ShowcaseCollectionResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $items = array_values(ShowcaseResource::collection(
            $this->collection->groupBy('product.uuid')
        )->resolve());

        return $items;
    }

    public function with($request){
        return [
            'success' => true
        ];
    }
}
