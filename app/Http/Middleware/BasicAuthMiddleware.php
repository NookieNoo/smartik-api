<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BasicAuthMiddleware
{
	/**
	 * Handle an incoming request.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \Closure $next
	 * @return mixed
	 */
	public function handle ($request, Closure $next) {
		$authenticationHasPassed = false;

		if ($request->header('PHP_AUTH_USER', null) && $request->header('PHP_AUTH_PW', null)) {
			$username = $request->header('PHP_AUTH_USER');
			$password = $request->header('PHP_AUTH_PW');

			if ($username === config('app.basic_auth.username') && $password === config('app.basic_auth.password')) {
				$authenticationHasPassed = true;
			}
		}

		if ($authenticationHasPassed === false) {
			return response()->make('Invalid credentials.', 401, ['WWW-Authenticate' => 'Basic']);
		}

		return $next($request);
	}
}