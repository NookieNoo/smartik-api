<?php

namespace App\Services;

use App\Services\ActiveApi\ActiveApiAction;
use Closure;
use HaydenPierce\ClassFinder\ClassFinder;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;

class MobxGeneratorService
{
	private Collection $resources;

	public function __construct () {

		$this->resources = collect(ClassFinder::getClassesInNamespace('App\Http\Resources', ClassFinder::RECURSIVE_MODE));
		return $this;
	}

	public static function resources (): MobxGeneratorService {
		return new static;
	}

	public function get (): Collection {
		return $this->getResourceInformation($this->resources->values());
	}

	private function getResourceInformation (array|Route|Collection $routes): Collection {
		if ($routes instanceof Route) {
			$routes = collect([$routes]);
		}
		if (is_array($routes)) {
			$routes = collect([...$routes]);
		}


		return $routes->map(function (Route $route) {
			if ($route->getActionName() === 'Closure') return;
			$action = new ActiveApiAction($route);
			return $action->toArray();
		});
	}

	public static function variableLengthToArray (array|string $variables): array {
		$result = [];

		foreach ($variables as $var) {
			if (is_array($var)) {
				$result = [...$result, ...$var];
			} else {
				$result = [...$result, $var];
			}
		}
		return array_unique($result);
	}
}