<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\SpatialBuilder;

class UserAddress extends Model
{
    use SoftDeletes;

    protected $casts = [
        'address_location' => Point::class,
        'default'          => 'boolean',
        'extra'            => 'object'
    ];

    public function user (): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function newEloquentBuilder ($query): SpatialBuilder
    {
        return new SpatialBuilder($query);
    }
}