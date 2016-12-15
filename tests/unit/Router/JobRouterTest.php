<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Disqontrol\Router;

use Disqontrol\Dispatcher\Call\Factory\CallFactoryInterface;
use Disqontrol\Job\Job;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Mockery as m;
use Disqontrol\Event\JobRouteEvent;
use Disqontrol\Event\Events;
use Disqontrol\Exception\JobRouterException;

class JobRouterTest extends \PHPUnit_Framework_TestCase
{

    const QUEUE = 'queue';
    const UNSUPPORTED_QUEUE = 'foobar';

    public function tearDown()
    {
        m::close();
    }

    public function testInstance()
    {
        $cf = $this->getMockBuilder(CallFactoryInterface::class)
            ->getMock();
        $ed = $this->getMockBuilder(EventDispatcher::class)
            ->getMock();
        $r = new JobRouter($cf, $ed);
        $this->assertInstanceOf(JobRouter::class, $r);
    }

    /**
     * Test that the proper event is dispatched and if an event listener
     * sets directions there, the call is returned from there.
     */
    public function testDirectionsFromEvent()
    {
        $directions = $this->getMockBuilder(WorkerDirections::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher = m::mock(EventDispatcher::class)
            ->shouldReceive('dispatch')
            ->with(Events::JOB_ROUTE, anInstanceOf(JobRouteEvent::class))
            ->andReturnUsing(function($eventName, $event) use ($directions) {
                $event->setWorkerDirections($directions);
            })
            ->getMock();

        $job = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->getMock();

        $callFactory = m::mock(CallFactoryInterface::class)
            ->shouldReceive('createCall')
            ->with($directions, $job)
            ->andReturn($directions)
            ->getMock();

        $router = new JobRouter($callFactory, $eventDispatcher);

        $this->assertSame($directions, $router->getCall($job));
    }

    /**
     * Test that if the router doesn't get a call from the event, it looks
     * into the registered routes.
     */
    public function testDirectionsFromRoute()
    {
        $directions = $this->getMockBuilder(WorkerDirections::class)
            ->disableOriginalConstructor()
            ->getMock();
        $job = new Job('body', self::QUEUE);

        $callFactory = m::mock(CallFactoryInterface::class)
            ->shouldReceive('createCall')
            ->with($directions, $job)
            ->andReturn($directions)
            ->getMock();
        $eventDispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->getMock();

        $router = new JobRouter($callFactory, $eventDispatcher);
        $route = new SimpleRoute([self::QUEUE], $directions);
        $router->addRoute($route);

        $this->assertSame($directions, $router->getCall($job));
    }

    /**
     * Test that a later added route overrides a previous one
     */
    public function testOverrideRoute()
    {
        $directions = $this->getMockBuilder(WorkerDirections::class)
            ->disableOriginalConstructor()
            ->getMock();
        $earlierRoute = new SimpleRoute([self::QUEUE], $directions);

        $calledDirections = $this->getMockBuilder(WorkerDirections::class)
            ->disableOriginalConstructor()
            ->getMock();
        $laterRoute = new SimpleRoute([self::QUEUE], $calledDirections);
        $job = new Job('body', self::QUEUE);

        $callFactory = m::mock(CallFactoryInterface::class)
            ->shouldReceive('createCall')
            ->with($calledDirections, $job)
            ->once()
            ->andReturn($calledDirections)
            ->getMock();
        $eventDispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->getMock();

        $router = new JobRouter($callFactory, $eventDispatcher);
        $router->addRoute($earlierRoute);
        $router->addRoute($laterRoute);

        $this->assertSame($calledDirections, $router->getCall($job));

    }

    /**
     * Test that if the event and the routes fail to produce a call, the router
     * throws an exception.
     */
    public function testExceptionOnNoWorker()
    {
        $callFactory = $this->getMockBuilder(CallFactoryInterface::class)
            ->getMock();
        $eventDispatcher = $this->getMockBuilder(EventDispatcher::class)
            ->getMock();
        $router = new JobRouter($callFactory, $eventDispatcher);

        $this->expectException(JobRouterException::class);

        $job = new Job('body', self::QUEUE);
        $router->getCall($job);
    }
}
