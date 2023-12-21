<?php

namespace App\Providers;

use App\Models\ApiUser;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
	/**
	 * The model to policy mappings for the application.
	 *
	 * @var array<class-string, class-string>
	 */
	protected $policies = [
		// 'App\Models\Model' => 'App\Policies\ModelPolicy',
	];

	/**
	 * Register any authentication / authorization services.
	 *
	 * @return void
	 */
	public function boot () {
		$this->registerPolicies();

        Auth::viaRequest('external-token', function (Request $request) {
            return ApiUser::where('token', $request->header('x-token'))->first();
        });
		//
	}
}
