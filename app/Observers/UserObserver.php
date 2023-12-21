<?php

namespace App\Observers;

use App\Models\User;
use App\Traits\WithUuid;

class UserObserver
{
    use WithUuid;

    public function creating (User $user)
    {
        $user->last_active_at = now();
    }
}