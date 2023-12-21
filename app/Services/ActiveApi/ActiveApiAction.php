<?php

namespace App\Services\ActiveApi;

use App\Services\ActiveApi\Attributes\BadResponse;
use App\Services\ActiveApi\Attributes\Controller;
use App\Services\ActiveApi\Attributes\Field;
use App\Services\ActiveApi\Attributes\Nested;
use App\Services\ActiveApi\Attributes\Position;
use App\Services\ActiveApi\Attributes\Response;
use App\Services\ActiveApi\Attributes\Title;
use App\Services\ActiveApi\Attributes\Variable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Routing\Route;

class ActiveApiAction
{
	private \ReflectionClass $reflection;
	private \ReflectionMethod $method;
	private string $uri;
	private string $title;
	private ?string $description = null;
	private string $slug;
	private array $methods;
	private array $params;
	private Collection $fields;
	private Collection $responses;
	private int $position;
	private Collection $variables;
	private bool $auth = false;

	public function __construct (
		private Route $route,
	) {
		[$controller, $action] = explode('@', $route->getActionName());
		$this->reflection = new \ReflectionClass($controller);
		$this->method = $this->reflection->getMethod($action);
		$this->uri = $this->route->uri();
		$this->title = count($this->method->getAttributes(Title::class)) ? $this->method->getAttributes(Title::class)[0]->getArguments()[0] : Str::title($action);
		$this->slug = count($this->method->getAttributes(Title::class)) && count($this->method->getAttributes(Title::class)[0]->getArguments()) > 1 ? $this->method->getAttributes(Title::class)[0]->getArguments()[1] : Str::slug($this->title);
		//$this->slug = $this->method->getAttributes(Title::class)[0]?->getArguments()[1] ?? Str::slug($this->title);
		$this->methods = $this->route->methods();
		$this->params = $this->route->parameterNames();
		$this->fields = $this->parseFields();
		$this->variables = $this->parseVariables();
		$this->auth = $this->parseAuth();
		$this->position = count($this->method->getAttributes(Position::class)) ? (int)$this->method->getAttributes(Position::class)[0]->getArguments()[0] : 99999;
		$this->responses = collect([...$this->method->getAttributes(Response::class), ...$this->method->getAttributes(BadResponse::class)]);
	}

	private function parseController (): array {
		$slug = false;
		$title = $this->reflection->getShortName();
		$title = str_replace('Controller', '', $title);

		if ($this->reflection->getAttributes(Title::class)) {
			$args = $this->reflection->getAttributes(Title::class)[0]->getArguments();
			$title = $args[0];
			if (count($args) > 1) {
				$slug = $args[1];
			}
		}

		if ($this->method->getAttributes(Controller::class)) {
			$args = $this->method->getAttributes(Controller::class)[0]->getArguments();
			$title = $args[0];
			$slug = Str::slug($title);
			if (count($args) > 1) {
				$slug = $args[1];
			}
		}

		return [
			'title'       => $title,
			'description' => null,
			'slug'        => $slug !== false ? $slug : Str::slug($title),
		];
	}

	private function parseVariables (): Collection {
		$variables = collect([]);

		foreach ($this->method->getAttributes(Variable::class) as $attribute) {
			$variableData = (new \ReflectionClass($attribute->getName()))->newInstanceArgs($attribute->getArguments());
			$find = $variables->where('name', $variableData->name)->first();
			if ($find) {
				$find->update($variableData);
			} else {
				$variables->push(ActiveApiVariable::parseVariable($variableData));
			}
		}

		return $variables;
	}

	private function parseFields (): Collection {
		$fields = collect([]);

		// note: $this->method->getParameters() === $this->route->signatureParameters()
		// but use signatureParameters from laravel
		$request = array_filter($this->route->signatureParameters(), fn($param) => $param->name === 'request')[0] ?? false;
		if ($request) {
			$request_class = $request->getType()->getName();
			$rules = method_exists($request_class, 'rules') ? (new $request_class)->rules() : [];
			foreach ($rules as $rule => $validations) {
				$fields->push(ActiveApiField::parseRule($rule, $validations));
			}
		}

		if ($this->method->getAttributes(Nested::class)) {

			$nested = $this->method->getAttributes(Nested::class)[0]->getArguments()[0];
			if (is_string($nested)) {
				$method = $this->reflection->getMethod($this->method->getAttributes(Nested::class)[0]->getArguments()[0]);
				foreach ($method->getAttributes(Field::class) as $attribute) {
					//$fields->add(ActiveApiField::parseParameter($param));
					$fieldData = (new \ReflectionClass($attribute->getName()))->newInstanceArgs($attribute->getArguments());
					$find = $fields->where('name', $fieldData->name)->first();
					if ($find) {
						$find->update($fieldData);
					} else {
						$fields->prepend(ActiveApiField::parseField($fieldData));
					}
				}
			}
		}

		foreach ($this->method->getAttributes(Field::class) as $attribute) {
			//$fields->add(ActiveApiField::parseParameter($param));
			$fieldData = (new \ReflectionClass($attribute->getName()))->newInstanceArgs($attribute->getArguments());
			$find = $fields->where('name', $fieldData->name)->first();
			if ($find) {
				$find->update($fieldData);
			} else {
				$fields->prepend(ActiveApiField::parseField($fieldData));
			}
		}

		return $fields;
	}

	private function parseAuth (): bool {
		foreach ($this->route->getAction()['middleware'] as $middleware) {
			if (str_starts_with($middleware, 'auth:')) {
				return true;
			}
		}
		return false;
	}

	public function toArray () {
		return [
			"controller"  => $this->parseController(),
			"slug"        => $this->slug,
			"title"       => $this->title,
			"description" => $this->description,
			'methods'     => $this->methods,
			"uri"         => $this->uri,
			"auth"        => $this->auth,
			"position"    => $this->position,
			"params"      => $this->params,
			"fields"      => $this->fields->map(fn(ActiveApiField $field) => $field->toArray())->toArray(),
			"variables"   => $this->variables->map(fn(ActiveApiVariable $variable) => $variable->toArray())->toArray(),
			"responses"   => $this->responses->map(fn($i) => ((new \ReflectionClass($i->getName()))->newInstanceArgs($i->getArguments()))->execute())->toArray(),
		];
	}
}