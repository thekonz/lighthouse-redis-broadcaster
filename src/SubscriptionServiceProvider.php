<?php

namespace thekonz\LighthouseRedisBroadcaster;

use Illuminate\Support\ServiceProvider;
use Nuwave\Lighthouse\Subscriptions\BroadcastManager as BaseBroadcastManager;
use Nuwave\Lighthouse\Subscriptions\Contracts\AuthorizesSubscriptions;
use Nuwave\Lighthouse\Subscriptions\Contracts\StoresSubscriptions;
use thekonz\LighthouseRedisBroadcaster\Broadcasting\BroadcastManager;
use thekonz\LighthouseRedisBroadcaster\Broadcasting\RedisBroadcaster;
use thekonz\LighthouseRedisBroadcaster\Console\LighthouseSubscribeCommand;
use thekonz\LighthouseRedisBroadcaster\Contracts\Broadcaster;
use thekonz\LighthouseRedisBroadcaster\Routing\Authorizer;
use thekonz\LighthouseRedisBroadcaster\Storage\Manager;

class SubscriptionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LighthouseSubscribeCommand::class,
            ]);
        }
    }

    public function register()
    {
        if ($this->app->runningInConsole()) {
            ini_set('default_socket_timeout', -1);
        }

        $this->mergeConfigFrom(__DIR__ . '/config.php', 'lighthouse.subscriptions.broadcasters.redis');

        $this->app->singleton(AuthorizesSubscriptions::class, Authorizer::class);
        $this->app->singleton(Broadcaster::class, RedisBroadcaster::class);
        $this->app->singleton(BaseBroadcastManager::class, BroadcastManager::class);
        $this->app->singleton(StoresSubscriptions::class, Manager::class);
    }
}
