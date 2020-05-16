<?php

namespace Tests\Broadcasting;

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Http\Request;
use Monolog\Test\TestCase;
use Nuwave\Lighthouse\Subscriptions\Subscriber;
use thekonz\LighthouseRedisBroadcaster\Broadcasting\RedisBroadcaster;
use thekonz\LighthouseRedisBroadcaster\Events\SubscriptionEvent;

class RedisBroadcasterTest extends TestCase
{
    public function testBroadcast()
    {
        $broadcastManager = $this->createMock(BroadcastManager::class);
        $broadcastManager->expects($this->once())
            ->method('event')
            ->with($this->callback(function (SubscriptionEvent $event) {
                return $event->broadcastAs() === 'lighthouse.subscription' &&
                    $event->broadcastOn()->name === 'presence-test-123' &&
                    $event->data === 'foo';
            }));

        $redisBroadcaster = new RedisBroadcaster($broadcastManager);
        $subscriber = $this->createMock(Subscriber::class);
        $subscriber->channel = 'test-123';

        $redisBroadcaster->broadcast($subscriber, ['data' => 'foo']);
    }

    public function testAuthorized()
    {
        $broadcastManager = $this->createMock(BroadcastManager::class);
        $redisBroadcaster = new RedisBroadcaster($broadcastManager);

        $request = new Request();
        $request['channel_name'] = 'abc';
        $request['socket_id'] = 'def';

        $response = $redisBroadcaster->authorized($request);
        $data = json_decode($response->content());
        $this->assertEquals(md5('abcdef'), $data->channel_data->user_id);
        $this->assertEquals(200, $response->status());
    }

    public function testUnauthorized()
    {
        $broadcastManager = $this->createMock(BroadcastManager::class);
        $redisBroadcaster = new RedisBroadcaster($broadcastManager);

        $response = $redisBroadcaster->unauthorized(new Request());
        $this->assertEquals(403, $response->status());
    }
}