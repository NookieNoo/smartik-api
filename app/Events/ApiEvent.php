<?php

namespace App\Events;

use App\Models\EventLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ApiEvent
{
    use Dispatchable, SerializesModels;

    public function __construct (
        public mixed                       $data = null,
        public Authenticatable|string|null $user = null,
        public mixed                       $extra = null
    )
    {
        if (!($user instanceof Authenticatable) && auth()->check()) {
            $this->user = auth()->user();
        }

        EventLog::create([
            'event'      => $this::class,
            'model_type' => $data instanceof Model ? get_class($data) : null,
            'model_id'   => $data instanceof Model ? $data->id : 0,
            'user_type'  => $this->user ? $this->user::class : "system",
            'user_id'    => $this->user->id ?? 0,
            'data'       => $this->data,
            'extra'      => $this->extra,
            // todo убрать при росте нагрузки
            'trace'      => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ]);
    }

    public function channels (): void {}
}