<?php


namespace thekonz\LighthouseRedisBroadcaster\Contracts;

use Nuwave\Lighthouse\Subscriptions\Subscriber;

/**
 * This is pretty much the same as \Nuwave\Lighthouse\Subscriptions\Contracts\Broadcaster,
 * but without the hook and auth for pusher, because we do not need an external
 * web hook and auth for our internally handled broadcasting.
 */
interface Broadcaster
{
    /**
     * Send data to subscriber.
     *
     * @param Subscriber $subscriber
     * @param mixed[] $data
     * @return void
     */
    public function broadcast(Subscriber $subscriber, array $data);
}
