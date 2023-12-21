<?php

namespace App\Helpers;

use DateTimeInterface;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\Support\UrlGenerator\BaseUrlGenerator;

class MediaLibraryUrlGenerator extends BaseUrlGenerator
{
    public function getUrl (): string
    {
        $url = $this->getDisk()->url($this->getPathRelativeToRoot());
        $resource = Str::lower(last(explode('\\', $this->media->model_type)));

        return config('app.url') . "/media/" . match ($resource) {
                "user"     => "avatar/" . $this->media->model?->uuid,
                "catalog"  => "catalog/" . $this->media->model?->uuid . '/big',
                "brand"    => "brand/" . $this->media->model?->slug . '/big',
                "provider" => "provider/" . $this->media->model?->slug . '/big',
                "product"  => "product/" . $this->media->model?->uuid . '/big',
                "banner"   => "banner/" . $this->media->model?->uuid . '/big',
            };
    }

    public function getTemporaryUrl (DateTimeInterface $expiration, array $options = []): string
    {
        return $this->getDisk()->temporaryUrl($this->getPathRelativeToRoot(), $expiration, $options);
    }

    public function getBaseMediaDirectoryUrl (): string
    {
        return $this->getDisk()->url('/');
    }

    public function getPath (): string
    {
        return $this->getRootOfDisk() . $this->getPathRelativeToRoot();
    }

    public function getResponsiveImagesDirectoryUrl (): string
    {
        $path = $this->pathGenerator->getPathForResponsiveImages($this->media);

        return Str::finish($this->getDisk()->url($path), '/');
    }

    protected function getRootOfDisk (): string
    {
        return $this->getDisk()->path('/');
    }
}
