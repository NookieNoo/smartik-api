<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Brand extends Model implements HasMedia, Sortable
{
    use SoftDeletes, InteractsWithMedia, SortableTrait;

    public function products (): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function registerMediaConversions (Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Manipulations::FIT_FILL, 128, 128)
            ->keepOriginalImageFormat()
            ->nonQueued();

        $this->addMediaConversion('big')
            ->fit(Manipulations::FIT_FILL, 512, 512)
            ->keepOriginalImageFormat()
            ->nonQueued();
    }

    public function registerMediaCollections (): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }
}