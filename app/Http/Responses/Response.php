<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class Response
{
	public static function send (mixed $data, $code = 200, ?array $meta = null): JsonResponse {
		$response = [
			'success' => true,
			'data'    => $data
		];

        if ($meta) $response['meta'] = $meta;

		return response()->json($response, $code ?? 200);
	}

    public static function sendRaw (mixed $data, $code = 200) {
        return response()->make($data, $code ?? 200);
	}

}
