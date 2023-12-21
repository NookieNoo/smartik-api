<?php

namespace App\Observers;

use App\Models\EventLog;

class EventLogObserver
{
    public function creating (EventLog $log)
    {
        if (!$log->model_id) {
            $log->model_id = 0;
        }
    }
}