<?php

namespace App\Events;

use App\Location;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class AddLocation
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public $location;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Location $location)
    {
        $this->location = $location;
        $this->location['slug'] = str_slug($this->location['name']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('slug-channel');
    }
}
