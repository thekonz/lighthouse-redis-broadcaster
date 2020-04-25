<?php


namespace thekonz\LighthouseRedisBroadcaster;

use Illuminate\Contracts\Container\BindingResolutionException;
use Nuwave\Lighthouse\Subscriptions\BroadcastManager as BaseBroadcastManager;
use thekonz\LighthouseRedisBroadcaster\Contracts\Broadcaster;

class BroadcastManager extends BaseBroadcastManager
{
    /**
     * @return string
     */
    public function interface(): string
    {
        return Broadcaster::class;
    }

    /**
     * @param array $config
     * @return RedisBroadcaster
     * @throws BindingResolutionException
     */
    public function createRedisDriver(array $config): RedisBroadcaster
    {
        return $this->app->make(Broadcaster::class);
    }
}
