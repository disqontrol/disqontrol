<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer\Process;

use Disqontrol\Consumer\Autoscale\AutoscaleAlgorithmFactory;
use Disqontrol\Consumer\Autoscale\ConstantProcessCount;
use Disqontrol\Dispatcher\Call\Cli\NullProcess;
use Mockery as m;
use Psr\Log\NullLogger;

class ProcessGroupFactoryTest extends \PHPUnit_Framework_TestCase
{
    const QUEUES = [];
    const MIN = 1;
    const MAX = 10;
    const BATCH = 5;

    public function testInstance()
    {
        $pgf = $this->createProcessGroupFactory();
        $this->assertInstanceOf(ProcessGroupFactory::class, $pgf);
    }

    public function testValuesAreIntegersAtLeast1()
    {
        $groupFactory = $this->createProcessGroupFactory();
        $processGroup = $groupFactory->create(self::QUEUES, -20, 'abc', self::BATCH);

        $this->assertGreaterThan(0, $processGroup->getMinProcessCount());
        $this->assertGreaterThan(0, $processGroup->getMaxProcessCount());
    }

    public function testCorrectMinMaxValues()
    {
        $groupFactory = $this->createProcessGroupFactory();
        $processGroup = $groupFactory->create(self::QUEUES, self::MAX, self::MIN, self::BATCH);
        $this->assertTrue($processGroup->getMaxProcessCount() === $processGroup->getMinProcessCount());
    }

    private function createProcessGroupFactory()
    {
        $process = new NullProcess();
        $processSpawner = m::mock(ConsumerProcessSpawner::class)
            ->shouldReceive('spawn')
            ->andReturn($process)
            ->getMock();

        $constantAutoscaling = new ConstantProcessCount(1);
        $autoscaleFactory = m::mock(AutoscaleAlgorithmFactory::class)
            ->shouldReceive('createConstantAlgorithm')
            ->andReturn($constantAutoscaling)
            ->getMock();

        $logger = new NullLogger();

        return new ProcessGroupFactory($processSpawner, $autoscaleFactory, $logger);
    }
}
