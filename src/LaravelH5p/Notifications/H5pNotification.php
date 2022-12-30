<?php

namespace InHub\LaravelH5p\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class H5pNotification extends Notification implements ShouldQueue
{
    public function handle($event)
    {
        //
    }

    public function failed($event, $exception)
    {
        //
    }
}
