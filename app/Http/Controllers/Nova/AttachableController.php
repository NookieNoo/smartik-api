<?php

namespace App\Http\Controllers\Nova;

use Illuminate\Routing\Controller;
use Laravel\Nova\Contracts\PivotableField;
use Laravel\Nova\Http\Requests\NovaRequest;

class AttachableController extends \Laravel\Nova\Http\Controllers\AttachableController
{
	/**
	 * List the available related resources for a given resource.
	 *
	 * @param \Laravel\Nova\Http\Requests\NovaRequest $request
	 * @return array
	 */
	public function __invoke (NovaRequest $request) {
		$field = $request->newResource()
			->availableFields($request)
			->filterForManyToManyRelations()
			->filter(function ($field) use ($request) {
				return $field->resourceName === $request->field &&
					$field->attribute === $request->viaRelationship;
			})->first();

		$withTrashed = $this->shouldIncludeTrashed(
			$request, $associatedResource = $field->resourceClass
		);

		$parentResource = $request->findResourceOrFail();

		return [
			'resources'   => $field->buildAttachableQuery($request, $withTrashed)
				->tap($this->getAttachableQueryResolver($request, $field))
				->get()
				->mapInto($field->resourceClass)
				->filter(function ($resource) use ($request, $parentResource) {
					return $parentResource->authorizedToAttach($request, $resource->resource);
				})
				->map(function ($resource) use ($request, $field) {
					return $field->formatAttachableResource($request, $resource);
				})->values(),
			'withTrashed' => $withTrashed,
			'softDeletes' => $associatedResource::softDeletes(),
		];
	}
}
