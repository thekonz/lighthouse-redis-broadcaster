<?php


namespace thekonz\LighthouseRedisBroadcaster\Contracts;

use Illuminate\Http\Request;
use Nuwave\Lighthouse\Subscriptions\Subscriber;
use Symfony\Component\HttpFoundation\Response;

/**
 * This is pretty much the same as \Nuwave\Lighthouse\Subscriptions\Contracts\Broadcaster,
 * but without the hook for pusher, because we do not need an external
 * web hook for our internally handled broadcasting.
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


    /**
     * Handle authorized subscription request.
     *
     * @param Request $request
     * @return Response
     */
    public function authorized(Request $request);

    /**
     * Handle unauthorized subscription request.
     *
     * @param Request $request
     * @return Response
     */
    public function unauthorized(Request $request);
}
