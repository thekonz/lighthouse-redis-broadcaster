<?php

namespace thekonz\LighthouseRedisBroadcaster\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SubscriptionEvent implements ShouldBroadcast
{
    /**
     * @var string
     */
    public $channel;

    /**
     * @var array
     */
    public $data;

    public function __construct(string $channel, array $data)
    {
        $this->channel = $channel;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new PresenceChannel($this->channel);
    }
}
