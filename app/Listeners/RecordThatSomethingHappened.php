<?php

namespace App\Listeners;

use App\Events\SomethingHappened;
use App\Models\Activity;

class RecordThatSomethingHappened
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SomethingHappened $event): void
    {
        Activity::create([
            'message' => $event->message,
        ]);
    }
}
