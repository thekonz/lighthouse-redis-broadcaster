<?php


namespace Tests\Broadcasting;


use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use thekonz\LighthouseRedisBroadcaster\Broadcasting\BroadcastManager;
use thekonz\LighthouseRedisBroadcaster\Contracts\Broadcaster;

class BroadcastManagerTest extends TestCase
{
    public function testDriver()
    {
        $app = new Application();

        $config = $this->createMock(Repository::class);
        $config->expects($this->once())
            ->method('get')
            ->with('lighthouse.subscriptions.broadcasters.redis')
            ->willReturn(['driver' => 'redis']);
        $app['config'] = $config;

        $broadcaster = $this->createMock(Broadcaster::class);
        $app->instance(Broadcaster::class, $broadcaster);

        $manager = new BroadcastManager($app);

        $this->assertEquals(
            $broadcaster,
            $manager->driver('redis')
        );
    }
}