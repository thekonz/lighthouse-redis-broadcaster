<?php

namespace thekonz\LighthouseRedisBroadcaster;

use Nuwave\Lighthouse\Subscriptions\Subscriber;
use thekonz\LighthouseRedisBroadcaster\Contracts\Broadcaster;

class RedisBroadcaster implements Broadcaster
{
    /**
     * @var \Illuminate\Contracts\Broadcasting\Broadcaster
     */
    private $broadcaster;

    public function __construct(\Illuminate\Broadcasting\BroadcastManager $broadcaster)
    {
        $this->broadcaster = $broadcaster;
    }

    /**
     * @param Subscriber $subscriber
     * @param array $data
     */
    public function broadcast(Subscriber $subscriber, array $data)
    {
        $this->broadcaster->event(
            new SubscriptionEvent($subscriber->channel, $data)
        );
    }
}
