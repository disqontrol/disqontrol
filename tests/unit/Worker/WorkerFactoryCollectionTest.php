<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Worker;

use Disqontrol\Exception\ConfigurationException;
use Mockery as m;

class WorkerFactoryCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WorkerFactoryCollection
     */
    private $wfc;
    
    const WORKER_NAME = 'worker';
    
    protected function setUp()
    {
        $this->wfc = new WorkerFactoryCollection();
    }

    public function tearDown()
    {
        m::close();
    }
    
    public function testInstance()
    {
        $this->assertInstanceOf(WorkerFactoryCollection::class, $this->wfc);
    }

    public function testEnvironmentSetupRunsOnlyOnce()
    {
        $envSetup = new TestEnvironmentSetup();
        $this->wfc->registerWorkerEnvironmentSetup([$envSetup, 'run']);

        $workerFactory = m::mock(WorkerFactoryInterface::class)
            ->shouldReceive('create')
            ->getMock();
        $this->wfc->addWorkerFactory(self::WORKER_NAME, $workerFactory);

        $this->assertEquals(0, $envSetup->runCount);

        $this->wfc->getWorker(self::WORKER_NAME);
        $this->wfc->getWorker(self::WORKER_NAME);
        $this->wfc->getWorker(self::WORKER_NAME);

        $this->assertEquals(1, $envSetup->runCount);
    }

    public function testWorkerFactoryReceivesEnvironment()
    {
        $envSetup = new TestEnvironmentSetup();
        $this->wfc->registerWorkerEnvironmentSetup([$envSetup, 'run']);

        $workerFactory = m::mock(WorkerFactoryInterface::class)
            ->shouldReceive('create')
            ->with(1, anything())
            ->getMock();
        $this->wfc->addWorkerFactory(self::WORKER_NAME, $workerFactory);

        $this->wfc->getWorker(self::WORKER_NAME);
    }

    public function testWorkerFactoryReceivesWorkerName()
    {
        $envSetup = new TestEnvironmentSetup();
        $this->wfc->registerWorkerEnvironmentSetup([$envSetup, 'run']);

        $workerFactory = m::mock(WorkerFactoryInterface::class)
            ->shouldReceive('create')
            ->with(anything(), self::WORKER_NAME)
            ->getMock();
        $this->wfc->addWorkerFactory(self::WORKER_NAME, $workerFactory);

        $this->wfc->getWorker(self::WORKER_NAME);
    }

    public function testExceptionWhenFactoryIsMissing()
    {
        $envSetup = new TestEnvironmentSetup();
        $this->wfc->registerWorkerEnvironmentSetup([$envSetup, 'run']);

        $this->expectException(ConfigurationException::class);

        try {
            $this->wfc->getWorker(self::WORKER_NAME);
        } catch (ConfigurationException $e) {
            // Rethrow for PHPUnit
            throw $e;
        } finally {
            // But also assert that the environment hasn't been setup
            $this->assertEquals(0, $envSetup->runCount);
        }
    }
}

class TestEnvironmentSetup
{
    public $runCount = 0;

    public function run()
    {
        $this->runCount++;
        return $this->runCount;
    }
}
