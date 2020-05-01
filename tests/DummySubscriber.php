<?php

namespace Tests;

use Nuwave\Lighthouse\Subscriptions\Subscriber;

class DummySubscriber extends Subscriber
{
    public function __construct(string $channel, ?string $topic = null)
    {
        $this->channel = $channel;
        $this->topic = $topic;
    }

    public function serialize(): string
    {
        return json_encode([
            'channel' => $this->channel,
            'topic' => $this->topic,
        ]);
    }

    public function unserialize($subscription): Subscriber
    {
        $data = json_decode($subscription);

        $this->channel = $data->channel;
        $this->topic = $data->topic;

        return $this;
    }
}