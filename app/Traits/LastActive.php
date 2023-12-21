<?php

namespace App\Traits;

use Carbon\Carbon;

trait LastActive
{

    protected function initializeLastActive ()
    {
        $this->mergeCasts([
            'last_active_at' => 'datetime'
        ]);
    }

    public function setLastActive ()
    {
        if (!$this->last_active_at || Carbon::now()->diffInMinutes($this->last_active_at) > 5) {
            $this->last_active_at = Carbon::now();
            $this->save();
        }
    }
}