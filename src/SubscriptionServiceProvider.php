<?php

namespace thekonz\LighthouseRedisBroadcaster;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\CachesConfiguration;
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
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'lighthouse.subscriptions.broadcasters.redis');
        if (! ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $this->createSubscriptionConnection($this->app['config']);
        }

        $this->app->singleton(AuthorizesSubscriptions::class, Authorizer::class);
        $this->app->singleton(Broadcaster::class, RedisBroadcaster::class);
        $this->app->singleton(BaseBroadcastManager::class, BroadcastManager::class);
        $this->app->singleton(StoresSubscriptions::class, Manager::class);
    }

    /**
     * We can not subscribe and run commands on the same connection in php,
     * so we just copy the desired redis connection to reuse it for both.
     * @param Repository $config
     */
    private function createSubscriptionConnection(Repository $config)
    {
        $connectionName = $config->get('lighthouse.broadcasters.redis.connection', 'default');
        $connectionConfig = $config->get('database.redis.' . $connectionName);

        $config->set('database.redis.lighthouse_subscription', array_merge(
            $connectionConfig,
            $config->get('database.redis.lighthouse_subscription', [])
        ));
    }
}
