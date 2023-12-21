<?php

namespace App\Services;

use App\Services\ActiveApi\ActiveApiAction;
use Closure;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;

class ActiveApiService
{
    private Router     $router;
    private Collection $routes;
    private Collection $filtered;

    public function __construct ()
    {

        $this->router = app()->get('router');

        //$this->router->flushMiddlewareGroups();
        $this->routes = collect($this->router->getRoutes());
        $this->filtered = $this->routes;

        return $this;
    }

    public static function routes (): ActiveApiService
    {
        return new static;
    }

    public function get (): Collection
    {
        return $this->getRouteInformation($this->filtered->values());
    }

    public function filter (Closure $filter): self
    {
        $this->filtered = $this->filtered->filter(function (Route $route) use ($filter) {
            return $filter($route);
        });
        return $this;
    }

    public function filterByUri (array|string ...$uri)
    {
        $uri = ActiveApiService::variableLengthToArray($uri);
        return $this->filter(function (Route $route) use ($uri) {
            foreach ($uri as $val) {
                if (str_ends_with($val, '*') && substr($route->uri, 0, strlen($val) - 1) === substr($val, 0, -1)) {
                    return true;
                } else if ($route->uri === $val) {
                    return true;
                }

                $route_arr = explode('/', $route->uri);
                $uri_arr = explode('/', $val);
                $match = true;
                foreach ($uri_arr as $k => $v) {
                    if (isset($route_arr[$k]) && str_starts_with($route_arr[$k], '{')) {
                        continue;
                    }
                    if (isset($route_arr[$k]) && $v === $route_arr[$k]) {
                        continue;
                    }
                    $match = false;
                    break;
                }

                return $match;
            }
        });
    }

    public function filterByMiddleware (array|string ...$middleware)
    {
        $middleware = ActiveApiService::variableLengthToArray($middleware);
        return $this->filter(function (Route $route) use ($middleware) {
            return @count(@array_intersect($this->router->gatherRouteMiddleware($route), $middleware)) ||
                @count(@array_intersect($route->computedMiddleware, $middleware));
        });
    }

    public function sort (): self
    {
        $this->filtered->sortBy('position');
        return $this;
    }

    private function getRouteInformation (array|Route|Collection $routes): Collection
    {
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

    public static function variableLengthToArray (array|string $variables): array
    {
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