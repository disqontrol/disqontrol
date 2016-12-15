<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Call\Php;

use Disqontrol\Job\Job;
use Disqontrol\Job\JobInterface;
use Disqontrol\Router\WorkerDirections;
use Disqontrol\Worker\WorkerInterface;
use Mockery as m;

class PhpCallTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }
    
    public function testInstance()
    {
        $phpCall = $this->createCall();
        $this->assertInstanceOf(PhpCall::class, $phpCall);
    }

    public function testMultipleCallsAreIdempotent()
    {
        $worker = new TestWorker();
        $phpCall = $this->createCall($worker);

        $this->assertEquals(0, $worker->callCount);
        $phpCall->call();
        $this->assertEquals(1, $worker->callCount);
        $phpCall->call();
        $this->assertEquals(1, $worker->callCount);
    }

    private function createCall($worker = null)
    {
        $directions = m::mock(WorkerDirections::class);
        if ($worker === null) {
            $worker = m::mock(WorkerInterface::class)
                ->shouldReceive('process')
                ->getMock();
        }
        $job = new Job('body', 'queue');

        $call = new PhpCall($directions, $worker, $job);

        return $call;
    }
}

class TestWorker implements WorkerInterface
{
    public $callCount = 0;

    public function process(JobInterface $job)
    {
        $this->callCount++;
    }
}
