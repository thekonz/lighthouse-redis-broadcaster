<?php

namespace Tests\Console;

use Illuminate\Config\Repository;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Contracts\Redis\Factory;
use Nuwave\Lighthouse\Subscriptions\Subscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputInterface;
use thekonz\LighthouseRedisBroadcaster\Console\LighthouseSubscribeCommand;
use thekonz\LighthouseRedisBroadcaster\Storage\Manager;

class LighthouseSubscribeCommandTest extends TestCase
{
    public function testSubscribe()
    {
        $redisConnection = $this->createMock(Connection::class);
        $redisConnection->expects($this->once())
            ->method('subscribe')
            ->with(
                ['PresenceChannelUpdated'],
                $this->isInstanceOf(\Closure::class)
            );

        $redisFactory = $this->mockRedisFactory($redisConnection);

        $output = $this->createMock(OutputStyle::class);
        $output->expects($this->once())
            ->method('writeln');

        $command = new LighthouseSubscribeCommand();
        $command->setOutput($output);

        $storageManager = $this->createMock(Manager::class);

        $command->handle($redisFactory, $storageManager);
    }

    public function testListen()
    {
        $redisConnection = $this->createMock(Connection::class);
        $redisConnection->expects($this->once())
            ->method('subscribe')
            ->willReturnCallback(function ($channel, \Closure $callback) use (&$listener) {
                $listener = $callback;
            });

        $redisFactory = $this->mockRedisFactory($redisConnection);
        $output = $this->createMock(OutputStyle::class);
        $input = $this->createMock(InputInterface::class);
        $storage = $this->createMock(Manager::class);

        $command = new LighthouseSubscribeCommand();
        $command->setOutput($output);
        $command->setInput($input);

        $command->handle($redisFactory, $storage);

        $channel = 'private-lighthouse-foo-1234';
        $events = [
            json_encode(['event' => ['channel' => 'presence-' . $channel . ':members', 'members' => []]]),
            json_encode(['event' => ['channel' => 'presence-' . $channel . ':members', 'members' => ['some user']]]),
            json_encode(['event' => ['channel' => 'presence-' . $channel . ':members', 'members' => []]]),
        ];

        $storage->expects($this->any())
            ->method('deleteSubscriber')
            ->willReturnCallback(function ($givenChannel) use (&$deleteCallCounter, $channel) {
                $deleteCallCounter++;
                $this->assertEquals($channel, $givenChannel);
            });

        call_user_func($listener, $events[0]);
        $this->assertEquals(0, $deleteCallCounter);

        call_user_func($listener, $events[1]);
        $this->assertEquals(0, $deleteCallCounter);

        call_user_func($listener, $events[2]);
        $this->assertEquals(1, $deleteCallCounter);
    }

    /**
     * @param $redisConnection
     * @return Factory|MockObject
     */
    private function mockRedisFactory($redisConnection)
    {
        $redisFactory = $this->createMock(Factory::class);
        $redisFactory->expects($this->once())
            ->method('connection')
            ->with('lighthouse_subscription')
            ->willReturn($redisConnection);

        return $redisFactory;
    }

    public function testLog()
    {
        $redisConnection = $this->createMock(Connection::class);
        $redisConnection->expects($this->once())
            ->method('subscribe')
            ->willReturnCallback(function ($channel, \Closure $callback) use (&$listener) {
                $listener = $callback;
            });

        $redisFactory = $this->mockRedisFactory($redisConnection);
        $storage = $this->createMock(Manager::class);
        $output = $this->createMock(OutputStyle::class);
        $input = $this->createMock(InputInterface::class);
        $input->expects($this->atLeastOnce())
            ->method('getOption')
            ->with('debug')
            ->willReturn(true);

        $command = new LighthouseSubscribeCommand();
        $command->setOutput($output);
        $command->setInput($input);

        $command->handle($redisFactory, $storage);

        $events = [
            json_encode(['event' => ['channel' => 'ignore-me:members', 'members' => []]]),
            json_encode(['event' => ['channel' => 'presence-private-lighthouse-123:members', 'members' => []]]),
            json_encode(['event' => ['channel' => 'presence-private-lighthouse-123:members', 'members' => ['foo', 'bar']]]),
            json_encode(['event' => ['channel' => 'presence-private-lighthouse-123:members', 'members' => []]]),
        ];

        $output->expects($this->any())
            ->method('getFormatter')
            ->willReturn($this->createMock(OutputFormatterInterface::class));

        $output->expects($this->exactly(5))
            ->method('writeln')
            ->withConsecutive(
                ['<warning>[debug] Ignored event for channel "ignore-me".</warning>'],
                ['<info>[debug] 0 members in channel "private-lighthouse-123".</info>'],
                ['<info>[debug] 2 members in channel "private-lighthouse-123".</info>'],
                ['<info>[debug] 0 members in channel "private-lighthouse-123".</info>'],
                ['<info>[debug] Deleted subscriber "bar" on topic "foo".</info>']
            );

        $subscriber = $this->createMock(Subscriber::class);
        $subscriber->topic = 'foo';
        $subscriber->channel = 'bar';
        $storage->expects($this->once())
            ->method('deleteSubscriber')
            ->willReturn($subscriber);

        call_user_func($listener, $events[0]);
        call_user_func($listener, $events[1]);
        call_user_func($listener, $events[2]);
        call_user_func($listener, $events[3]);
    }
}
