<?php

namespace App\Http\Middleware;

use App\Models\DeviceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class UserDeviceMiddleware
{
	public function handle (Request $request, \Closure $next) {

		if (auth()->check()) {
			$user = auth()->user();
			if ($request->header('x-app') && method_exists($user, 'device')) {
				$app_header = json_decode(base64_decode($request->header('x-app')), false);
				if ($app_header) {

					$data = [
						'uuid'         => $app_header->uuid,
						'brand'        => $app_header->brand,
						'manufacturer' => $app_header->manufacturer,
						'model_name'   => $app_header->modelName,
						'os_name'      => $app_header->osName,
						'os_version'   => $app_header->osVersion,
						'device_name'  => $app_header->deviceName,
						'app_version'  => $app_header->appVersion,
					];

					if (!$user->device) {
						$user->device()->create($data);
					} else {
						$device = $user->device->fill($data);
						if ($device->isDirty()) {
							DeviceHistory::create([
								'user_type' => get_class($user),
								'user_id'   => $user->id,
								'device'    => Arr::except($device->getOriginal(), ['user_type', 'user_id', 'created_at', 'updated_at']),
							]);
							$device->save();
						}
					}
				}
			}
		}

		return $next($request);
	}
}