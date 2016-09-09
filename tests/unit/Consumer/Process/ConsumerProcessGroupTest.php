<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer\Process;

use Disqontrol\Consumer\Autoscale\ConstantProcessCount;
use Psr\Log\NullLogger;
use Mockery as m;

class ConsumerProcessGroupTest extends \PHPUnit_Framework_TestCase
{
    const QUEUES = [];
    const MIN = 1;
    const MAX = 10;
    const BATCH = 5;

    public function tearDown()
    {
        m::close();
    }

    public function testInstance()
    {
        $cpg = $this->createConsumerProcessGroup();
        $this->assertInstanceOf(ConsumerProcessGroup::class, $cpg);
    }

    public function testStartRightNumberOfProcesses()
    {
        $min = 3;
        $processSpawner = m::mock(ConsumerProcessSpawner::class)
            ->shouldReceive('spawn')
            ->times($min)
            ->getMock();

        $max = null;
        $this->createConsumerProcessGroup($min, $max, $processSpawner);
    }

    public function testAddBurstProcesses()
    {
        // 3 are spawned, then 2 more
        $min = 3;
        $max = 12;

        $requiredBurstCount = 2;

        $noBurst = false;
        $burst = true;

        $nullConsumer = m::mock(ConsumerProcess::class)
            ->shouldReceive('isRunning')
            ->andReturn(true)
            ->shouldReceive('stop')
            ->getMock();

        $processSpawner = m::mock(ConsumerProcessSpawner::class)
            ->shouldReceive('spawn')
            ->with(anything(), anything(), $noBurst)
            ->times($min)
            ->andReturn($nullConsumer)
            ->shouldReceive('spawn')
            ->with(anything(), anything(), $burst)
            ->times($requiredBurstCount)
            ->getMock();

        $autoscale = new ConstantProcessCount($min + $requiredBurstCount);

        $consumers = $this->createConsumerProcessGroup($min, $max, $processSpawner, $autoscale);
        $consumers->checkOnConsumers();
    }

    public function testDontExceedMaximumProcessCount()
    {
        // 3 are spawned, then we require 10 more, but spawn only up to max
        $min = 3;
        $max = 5;

        $requiredBurstCount = 10;

        $noBurst = false;
        $burst = true;

        $nullConsumer = m::mock(ConsumerProcess::class)
            ->shouldReceive('isRunning')
            ->andReturn(true)
            ->shouldReceive('stop')
            ->getMock();

        $processSpawner = m::mock(ConsumerProcessSpawner::class)
            ->shouldReceive('spawn')
            ->with(anything(), anything(), $noBurst)
            ->times($min)
            ->andReturn($nullConsumer)
            ->shouldReceive('spawn')
            ->with(anything(), anything(), $burst)
            ->times($max - $min)
            ->getMock();

        $autoscale = new ConstantProcessCount($min + $requiredBurstCount);

        $consumers = $this->createConsumerProcessGroup($min, $max, $processSpawner, $autoscale);
        $consumers->checkOnConsumers();
    }

    public function testRestartDeadPermanentProcesses()
    {
        // 2 are spawned, 1 dies and is respawned
        $min = 2;
        $max = 5;

        $requiredBurstCount = 2;

        $noBurst = false;
        $burst = true;

        $deadConsumer = m::mock(ConsumerProcess::class)
            ->shouldReceive('isRunning')
            ->andReturn(false)
            ->shouldReceive('stop')
            ->getMock();
        $runningConsumer = m::mock(ConsumerProcess::class)
            ->shouldReceive('isRunning')
            ->andReturn(true)
            ->shouldReceive('stop')
            ->getMock();

        $processSpawner = m::mock(ConsumerProcessSpawner::class)
            ->shouldReceive('spawn')
            ->with(anything(), anything(), $noBurst)
            ->times($min + 1)
            ->andReturn($deadConsumer, $runningConsumer)
            ->shouldReceive('spawn')
            ->with(anything(), anything(), $burst)
            ->times($requiredBurstCount)
            ->getMock();

        $autoscale = new ConstantProcessCount($min + $requiredBurstCount);

        $consumers = $this->createConsumerProcessGroup($min, $max, $processSpawner, $autoscale);
        $consumers->checkOnConsumers();
    }

    /**
     * @param int $min            Minimum number of processes
     * @param int $max            Maximum number of processes
     * @param     $processSpawner
     * @param     $autoscale
     *
     * @return ConsumerProcessGroup
     */
    private function createConsumerProcessGroup(
        $min = null,
        $max = null,
        $processSpawner = null,
        $autoscale = null
    ) {
        if (empty($processSpawner)) {
            $processSpawner = $this->getMockBuilder(ConsumerProcessSpawner::class)
                ->disableOriginalConstructor()
                ->getMock();
        }

        if (empty($autoscale)) {
            $autoscale = new ConstantProcessCount(1);
        }

        $logger = new NullLogger();

        if (empty($min)) {
            $min = self::MIN;
        }
        if (empty($max)) {
            $max = self::MAX;
        }

        return new ConsumerProcessGroup(
            self::QUEUES,
            $min,
            $max,
            self::BATCH,
            $processSpawner,
            $autoscale,
            $logger
        );
    }
}
