<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Banner extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $casts = [
        'is_published' => 'boolean'
    ];

    public function bannerable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'model_type', 'model_id');
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                if ($this->bannerable instanceof Product) {
                    return 'product';
                } else {
                    return 'catalog';
                }
            },
        );
    }

    protected function modelUuid(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return $this->bannerable->uuid;
            },
        );
    }

    protected function location(): Attribute
    {
        return Attribute::make(
            get: fn() => 'home_header',
        );
    }

    public function registerMediaConversions (Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(128)
            ->keepOriginalImageFormat()
            ->nonQueued();

        $this->addMediaConversion('big')
            ->width(512)
            ->keepOriginalImageFormat()
            ->nonQueued();
    }

    public function registerMediaCollections (): void
    {
        $this->addMediaCollection('banner')->singleFile();
    }
}
