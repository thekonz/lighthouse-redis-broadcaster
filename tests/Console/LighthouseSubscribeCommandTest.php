<?php

namespace Tests\Console;

use Illuminate\Config\Repository;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Contracts\Redis\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use thekonz\LighthouseRedisBroadcaster\Console\LighthouseSubscribeCommand;
use thekonz\LighthouseRedisBroadcaster\Storage\Manager;

class LighthouseSubscribeCommandTest extends TestCase
{
    public function testSubscribe()
    {
        $config = $this->mockConfigRepository();

        $redisConnection = $this->createMock(Connection::class);
        $redisConnection->expects($this->once())
            ->method('subscribe')
            ->with(
                'PresenceChannelUpdated',
                $this->isInstanceOf(\Closure::class)
            );

        $redisFactory = $this->mockRedisFactory($redisConnection);

        $output = $this->createMock(OutputStyle::class);
        $output->expects($this->once())
            ->method('writeln');

        $command = new LighthouseSubscribeCommand();
        $command->setOutput($output);

        $storageManager = $this->createMock(Manager::class);

        $command->handle($config, $redisFactory, $storageManager);
    }

    public function testListen()
    {
        $config = $this->mockConfigRepository();

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

        $command->handle($config, $redisFactory, $storage);

        $channel = 'presence-lighthouse-foo-1234';
        $events = [
            json_encode(['event' => ['channel' => $channel . ':members', 'members' => []]]),
            json_encode(['event' => ['channel' => $channel . ':members', 'members' => ['some user']]]),
            json_encode(['event' => ['channel' => $channel . ':members', 'members' => []]]),
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
            ->with('foobar')
            ->willReturn($redisConnection);

        return $redisFactory;
    }

    /**
     * @return Repository|MockObject
     */
    private function mockConfigRepository()
    {
        $config = $this->createMock(Repository::class);
        $config->expects($this->once())
            ->method('get')
            ->with('lighthouse.broadcasters.redis.connection', 'default')
            ->willReturn('foobar');

        return $config;
    }
}
