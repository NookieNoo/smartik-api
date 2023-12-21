<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Kalnoy\Nestedset\NodeTrait;
use Novius\LaravelNovaOrderNestedsetField\Traits\Orderable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Catalog extends Model implements HasMedia
{
    use NodeTrait, Orderable, InteractsWithMedia, HasRelationships;

    protected $casts = [
        'hidden' => 'boolean'
    ];

    public function products (): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_catalog');
    }

    public function actuals (): HasManyDeep
    {
        return $this->hasManyDeep(ProductActual::class, ['product_catalog', Product::class]);
    }

    public function banner(): MorphMany
    {
        return $this->morphMany(Banner::class, 'bannerable');
    }

    public function registerMediaConversions (Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(128)
            ->height(128)
            ->keepOriginalImageFormat()
            ->nonQueued();

        $this->addMediaConversion('big')
            ->width(512)
            ->height(512)
            ->keepOriginalImageFormat()
            ->nonQueued();
    }

    public function registerMediaCollections (): void
    {
        $this->addMediaCollection('icon')->singleFile();
    }

    public function getLftName (): string
    {
        return 'left';
    }

    public function getRgtName (): string
    {
        return 'right';
    }
}
