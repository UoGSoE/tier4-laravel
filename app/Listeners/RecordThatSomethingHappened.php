<?php

namespace App\Listeners;

use App\Events\SomethingHappened;
use App\Models\Activity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
