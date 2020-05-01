<?php


namespace thekonz\LighthouseRedisBroadcaster\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Redis\Factory;
use Nuwave\Lighthouse\Subscriptions\Contracts\StoresSubscriptions;

class LighthouseSubscribeCommand extends Command
{
    protected $signature = 'lighthouse:subscribe {--debug}';

    protected $description = 'Subscribe to graphql related redis events';

    /**
     * @var StoresSubscriptions
     */
    private $storage;

    /**
     * A map of channelName => memberCount.
     * @var array
     */
    private $knownChannels;

    public function handle(Repository $config, Factory $redis, StoresSubscriptions $storage)
    {
        $this->storage = $storage;
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
        $memberCount = count($event->members);
        $channel = $this->sanitizeChannelName($event->channel);

        // We only care about the lighthouse presence channel events.
        if (!$this->isLighthouseChannel($channel)) {
            return $this->logIgnoreEvent($channel);
        }

        $this->logEvent($memberCount, $channel);

        // The laravel echo server sends one event before joining and one after.
        // So the first event has member count 0, but we do not know the channel.
        if ($memberCount === 0 && isset($this->knownChannels[$channel])) {
            // Someone left a channel that we know from before and it is now empty.
            return $this->deleteSubscriber($channel);
        }

        $this->knownChannels[$channel] = $memberCount;
    }

    /**
     * @param int $memberCount
     * @param string $channel
     */
    private function logEvent(int $memberCount, string $channel): void
    {
        if (!$this->option('debug')) {
            return;
        }

        $this->info(sprintf(
            '[debug] %d members in channel "%s"',
            $memberCount,
            $channel
        ));
    }

    /**
     * @param string $channel
     */
    private function deleteSubscriber(string $channel)
    {
        $this->storage->deleteSubscriber($channel);
        unset($this->knownChannels[$channel]);
    }

    /**
     * @param string $channel
     * @return bool
     */
    private function isLighthouseChannel(string $channel): bool
    {
        return strpos($channel, 'presence-lighthouse-') === 0;
    }

    /**
     * @param string $channel
     */
    private function logIgnoreEvent(string $channel)
    {
        if (!$this->option('debug')) {
            return;
        }

        $this->warn(
            sprintf('[debug] Ignored event for channel "%s"', $channel)
        );
    }

    /**
     * Sanitizes the supposed channel name and returns the actual channel name.
     * The echo server will send an event with a channel named "channel-name:members".
     * @param string $channel
     * @return string
     */
    private function sanitizeChannelName(string $channel): string
    {
        [$channelName] = explode(':', $channel);
        return $channelName;
    }
}
