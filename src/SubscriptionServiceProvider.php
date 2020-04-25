<?php

namespace thekonz\LighthouseRedisBroadcaster;

use Illuminate\Support\ServiceProvider;
use Nuwave\Lighthouse\Subscriptions\BroadcastManager as BaseBroadcastManager;
use Nuwave\Lighthouse\Subscriptions\Contracts\StoresSubscriptions;
use thekonz\LighthouseRedisBroadcaster\Contracts\Broadcaster;

class SubscriptionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RedisSubscribeGraphqlCommand::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->singleton(Broadcaster::class, RedisBroadcaster::class);
        $this->app->singleton(BaseBroadcastManager::class, BroadcastManager::class);
        $this->app->singleton(StoresSubscriptions::class, StorageManager::class);
    }
}
