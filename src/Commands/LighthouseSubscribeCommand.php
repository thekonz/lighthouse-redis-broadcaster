<?php


namespace thekonz\LighthouseRedisBroadcaster\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Redis\Factory;

class LighthouseSubscribeCommand extends Command
{
    protected $signature = 'lighthouse:subscribe';

    protected $description = 'Subscribe to graphql related redis events';

    public function handle(Repository $config, Factory $redis)
    {
        ini_set('default_socket_timeout', -1);

        $this->info('Listening to events...');

        $redis->connection(
            $config->get('lighthouse.broadcasters.redis.connection', 'default')
        )->subscribe(
            'PresenceChannelUpdated',
            \Closure::fromCallable([$this, 'handleSubscriptionEvent'])
        );
    }

    private function handleSubscriptionEvent(string $message)
    {
        $payload = json_decode($message);
        $event = $payload->event;
        $this->info(sprintf(
            '[debug] %d members in channel "%s": %s',
            $event->channel,
            count($event->members),
            $message
        ));
    }
}
