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
     * @var mixed
     */
    public $data;

    public function __construct(string $channel, $data)
    {
        $this->channel = $channel;
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return new PresenceChannel($this->channel);
    }

    public function broadcastAs()
    {
        return 'lighthouse.subscription';
    }
}
