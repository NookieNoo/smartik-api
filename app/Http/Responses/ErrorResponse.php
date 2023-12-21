<?php

namespace App\Http\Responses;

use App\Exceptions\ApiException;
use Illuminate\Http\JsonResponse;

class ErrorResponse
{
	private static ?\Throwable $exception = null;

	public static function exception (\Throwable $exception, $code = null): JsonResponse {
		static::$exception = $exception;
		$class = last(explode('\\', get_class($exception)));
		$find_exists = 'App\\Exceptions\\Vendor\\' . $class;
		if (class_exists($find_exists)) {

			return static::response(new $find_exists($exception));
		}

		return static::response(new ApiException(
			type: method_exists($exception, 'getType') ? $exception->getType() : 'logic',
			message: $exception->getMessage() ? $exception->getMessage() : $class,
			code: $code ?? 422,
			data: $exception->data ?? null
		));
	}

	public static function send (mixed $data, $code = null): JsonResponse {
		return static::response(new ApiException(
			type: 'logic',
			message: $data,
			code: $code ?? 422
		));
	}

	private static function response (ApiException $error): JsonResponse {
		$response = [
			'success' => false,
			'error'   => [
				'type'    => $error->type,
				'message' => $error->message,
				'data'    => $error->data
			]
		];

		if (app()->environment() !== 'production' && $response['error']['data'] === null && request()->ip() === '91.218.85.179') {
			$response['error']['data'] = [
				'trace'   => static::$exception?->getTrace(),
				'message' => static::$exception?->getMessage(),
			];
		}

		return response()->json($response, $error->code);
	}
}