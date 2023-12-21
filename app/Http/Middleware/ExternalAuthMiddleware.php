<?php

namespace App\Http\Middleware;

use App\Attributes\ExternalSalt;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

class ExternalAuthMiddleware
{
    public function handle (Request $request, Closure $next)
    {
        $class = new \ReflectionClass(Route::getCurrentRoute()->controller::class);
        $salt = $class->getAttributes(ExternalSalt::class)[0]->getArguments()[0] ?? false;
        if (!$salt) return response()->json(['success' => false, 'error' => 'auth'], 403);

        $data = [];

        foreach ($request->all() ?? [] as $key => $value) {
            $data[$key] = $value;
        }

        ksort($data);

        if ($request->input('hash') !== md5(implode('', Arr::except($data, ['hash'])) . $salt)) {
            return response()->json([
                'success' => false, 'error' => 'auth'
            ], 401);
        }
        return $next($request);
    }
}