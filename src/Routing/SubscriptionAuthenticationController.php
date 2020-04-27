<?php

namespace thekonz\LighthouseRedisBroadcaster\Routing;

use Illuminate\Http\Request;
use Nuwave\Lighthouse\Subscriptions\Contracts\BroadcastsSubscriptions;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionAuthenticationController
{
    /**
     * @var BroadcastsSubscriptions
     */
    private $broadcaster;

    public function __construct(BroadcastsSubscriptions $broadcaster)
    {
        $this->broadcaster = $broadcaster;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        return $this->broadcaster->authorize($request);
    }
}
