<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class LastActiveMiddleware
{
	public function handle (Request $request, \Closure $next) {
		if (auth()->check()) {
			$user = auth()->user();
			if (method_exists($user, 'setLastActive')) {
				$user->setLastActive();
			}
		}
		return $next($request);
	}
}