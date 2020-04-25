<?php


namespace thekonz\LighthouseRedisBroadcaster;

use Illuminate\Console\Command;
use Illuminate\Contracts\Redis\Factory;

class RedisSubscribeGraphqlCommand extends Command
{
    protected $signature = 'redis:subscribe-graphql';

    protected $description = 'Subscribe to graphql related redis events';

    public function handle(Factory $redis)
    {
        // @todo check out the correct connection or make it configurable
        $redis->connection()->subscribe('PresenceChannelUpdated', function ($message) {
            $payload = json_decode($message);
            $event = $payload->event;
            $this->info(sprintf(
                '[debug] %d members in channel "%s": %s',
                $event->channel,
                count($event->members),
                $message
            ));
        });
    }
}
