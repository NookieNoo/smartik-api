<?php

namespace App\Helpers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class MediaLibraryPathGenerator implements PathGenerator
{
	public function getPath (Media $media): string {
		return $media->id . '/conversions/';
	}

	public function getPathForConversions (Media $media): string {
		return $this->getPath($media);
	}

	public function getPathForResponsiveImages (Media $media): string {
		return $this->getPath($media);
	}
}