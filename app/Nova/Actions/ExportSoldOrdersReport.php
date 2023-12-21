<?php

namespace App\Nova\Actions;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;
use Rap2hpoutre\FastExcel\FastExcel;
use Laravel\Nova\Fields\Date;

class ExportSoldOrdersReport extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Отчет по продажам';
    public $responseType = 'blob';
    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $start = $fields->start;
        $finish = $fields->finish;
        return Action::openInNewTab(url("/user/sold_orders_report?start=$start&finish=$finish"));
//        return Action::redirect("/user/order/sold_orders_report?start=$start&finish=$finish");
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Date::make('Start')
                ->placeholder('Дата начала')
                ->default(function ($request) {
                    return Carbon::now()->sub('30','day')->format('Y-m-d');
                }),
            Date::make('Finish'),
        ];
    }
}
