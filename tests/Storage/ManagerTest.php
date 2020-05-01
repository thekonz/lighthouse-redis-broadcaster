<?php


namespace Tests\Storage;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Contracts\Redis\Factory;
use Nuwave\Lighthouse\Subscriptions\Subscriber;
use PHPUnit\Framework\TestCase;
use Tests\DummySubscriber;
use thekonz\LighthouseRedisBroadcaster\Storage\Manager;

class ManagerTest extends TestCase
{
    public function testSubscriberByChannel()
    {
        $config = $this->createMock(Repository::class);
        $redisConnection = $this->createMock(Connection::class);
        $redisFactory = $this->createMock(Factory::class);
        $redisFactory->expects($this->once())
            ->method('connection')
            ->willReturn($redisConnection);

        $channel = 'test-channel';
        $subscriber = new DummySubscriber($channel, 'test-topic');
        $redisConnection->expects($this->exactly(2))
            ->method('command')
            ->with('get', ['graphql.subscriber.' . $channel])
            ->willReturn(serialize($subscriber));

        $manager = new Manager($config, $redisFactory);

        $retrievedSubscriber = $manager->subscriberByChannel($channel);
        $this->assertEquals($subscriber, $retrievedSubscriber);

        $retrievedSubscriber = $manager->subscriberByRequest(['channel_name' => $channel], []);
        $this->assertEquals($subscriber, $retrievedSubscriber);

        $this->assertNull($manager->subscriberByRequest([], []));
    }

    public function testDeleteSubscriber()
    {
        $config = $this->createMock(Repository::class);
        $redisConnection = $this->createMock(Connection::class);
        $redisFactory = $this->createMock(Factory::class);
        $redisFactory->expects($this->once())
            ->method('connection')
            ->willReturn($redisConnection);

        $channel = 'test-channel';
        $prefixedChannel = 'graphql.subscriber.' . $channel;
        $subscriber = new DummySubscriber($channel, 'test-topic');
        $redisConnection->expects($this->at(0))
            ->method('command')
            ->with('get', [$prefixedChannel])
            ->willReturn(serialize($subscriber));

        $redisConnection->expects($this->at(1))
            ->method('command')
            ->with('del', [$prefixedChannel]);

        $redisConnection->expects($this->at(2))
            ->method('command')
            ->with('srem', ['graphql.topic.' . $subscriber->topic, $channel]);

        $manager = new Manager($config, $redisFactory);
        $retrievedSubscriber = $manager->deleteSubscriber($channel);
        $this->assertEquals($subscriber, $retrievedSubscriber);
    }

    public function testStoreSubscriber()
    {
        $config = $this->createMock(Repository::class);
        $redisConnection = $this->createMock(Connection::class);
        $redisFactory = $this->createMock(Factory::class);
        $redisFactory->expects($this->once())
            ->method('connection')
            ->willReturn($redisConnection);

        $redisConnection->expects($this->at(0))
            ->method('command')
            ->with('sadd', [
                'graphql.topic.some-topic',
                'presence-lighthouse-foo',
            ]);

        $subscriber = new DummySubscriber('private-lighthouse-foo');
        $redisConnection->expects($this->at(1))
            ->method('command')
            ->with('set', [
                'graphql.subscriber.presence-lighthouse-foo',
                'C:21:"Tests\DummySubscriber":58:{' . json_encode([
                    'channel' => 'presence-lighthouse-foo',
                    'topic' => 'some-topic',
                ]) . '}',
            ]);

        $manager = new Manager($config, $redisFactory);
        $manager->storeSubscriber($subscriber, 'some-topic');
    }

    public function testFoo()
    {
        $config = $this->createMock(Repository::class);
        $redisConnection = $this->createMock(Connection::class);
        $redisFactory = $this->createMock(Factory::class);
        $redisFactory->expects($this->once())
            ->method('connection')
            ->willReturn($redisConnection);

        $topic = 'bar';
        $subscribers = [
            new DummySubscriber('foo1', $topic),
            new DummySubscriber('foo2', $topic),
        ];

        $redisConnection->expects($this->at(0))
            ->method('command')
            ->with('smembers', ['graphql.topic.' . $topic])
            ->willReturn(['foo1', 'foo2']);

        $redisConnection->expects($this->at(1))
            ->method('command')
            ->with('mget', ['graphql.subscriber.foo1', 'graphql.subscriber.foo2'])
            ->willReturn(array_map('serialize', $subscribers));

        $manager = new Manager($config, $redisFactory);
        $this->assertEquals(
            $subscribers,
            $manager->subscribersByTopic($topic)->all()
        );
    }
}
