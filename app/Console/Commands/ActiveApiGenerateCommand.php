<?php

namespace App\Console\Commands;

use App\Services\ActiveApiService;
use Illuminate\Console\Command;

class ActiveApiGenerateCommand extends Command
{
    protected $signature = 'activeapi:generate';

    protected $description = 'Command description';

    public function handle ()
    {
        $this->line('Start generating json');
        $result = [
            'info' => [
                'generated_at' => time(),
                'name'         => 'DEV smartik',
                'text'         => 'API documentation',
                'url'          => config('app.url') . '/',
                'auth'         => [
                    'enable'      => true,
                    'type'        => 'bearer',
                    'description' => 'Authorization: Bearer'
                ],
                'header'       => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
            ]
        ];

        $routes = collect(ActiveApiService::routes()->filterByMiddleware('api')->sort()->get());

        $this->line('Find routes: ' . $routes->count());

        $result['variable'] = array_values($routes->filter(function ($item) {
            return (bool)count($item['variables']);
        })->map(function ($item) use ($result, $routes) {
            return collect($item['variables'])->map(function ($variable) use ($item) {
                return [
                    'data' => [
                        'id'      => $variable['slug'],
                        'type'    => $variable['type'],
                        'name'    => $variable['title'] ?? $variable['slug'],
                        'text'    => $variable['description'],
                        'eval'    => $variable['response'],
                        'group'   => 'user',
                        'version' => 'master',
                    ],
                    'from' => [
                        'controller' => $item['controller']['slug'],
                        'action'     => $item['slug'],
                        'url'        => $item['uri'],
                    ]
                ];
            })->first();
        })->toArray());

        $result['api']['main']['data']['master']['data'] = array_values($routes->groupBy('controller.slug')->sortBy('position')->map(function ($controller) use ($result) {
            $this->info('Parse controller: ' . $controller->first()['controller']['title']);
            return [
                'id'     => $controller->first()['controller']['slug'],
                'name'   => $controller->first()['controller']['title'],
                'text'   => $controller->first()['description'],
                'tags'   => [],
                'action' => array_values($controller->sortBy('position')->map(function ($action) {
                    $this->info('-- Parse action: ' . $action['title'] . ' (' . $action['uri'] . ') ' . $action['position']);
                    return [
                        'id'     => $action['slug'],
                        'name'   => $action['title'],
                        'text'   => $action['description'],
                        'tags'   => [],
                        'auth'   => $action['auth'],
                        'url'    => $action['uri'],
                        'method' => $action['methods'][0],
                        'param'  => $action['params'],
                        'field'  => collect($action['fields'])->map(function ($field) {
                            return [
                                'id'       => $field['slug'],
                                'name'     => $field['title'],
                                'text'     => $field['description'],
                                'type'     => $field['type'],
                                'nullable' => $field['nullable'],
                                'required' => $field['required'],
                                'extra'    => $field['extra'],
                                'rules'    => $field['validations'],
                                'rulesRaw' => $field['validations'],
                                'ignore'   => $field['ignore'],
                                'default'  => $field['default'],
                                'samples'  => $field['samples'],
                            ];
                        }),
                    ];
                })->toArray()),
            ];
        })->toArray());

        $this->line('Write file: ' . public_path('activeapi/api.json'));

        if (!file_exists(public_path('activeapi'))) {
            mkdir(public_path('activeapi'), 0777, true);
        }
        file_put_contents(public_path('activeapi/api.json'), json_encode($result));
        $this->info('done');
    }
}
