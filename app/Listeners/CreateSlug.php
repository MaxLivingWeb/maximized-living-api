<?php

namespace App\Listeners;

use App\Events\AddLocation;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateSlug
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AddLocation  $event
     * @return void
     */
    public function handle(AddLocation $event)
    {
        //
    }
}
