<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Failure;

use Disqontrol\Configuration\Configuration;
use Mockery as m;

class FailureStrategyCollectionTest extends \PHPUnit_Framework_TestCase
{
    const FAILURE_STRATEGY_ID = 'fs';

    public function tearDown()
    {
        m::close();
    }

    public function testInstance()
    {
        $repository = $this->createRepository();
        $this->assertInstanceOf(FailureStrategyCollection::class, $repository);
    }

    public function testGetFailureStrategy()
    {
        $repository = $this->createRepository();
        $strategy = $this->getMockBuilder(FailureStrategyInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->addFailureStrategy(self::FAILURE_STRATEGY_ID, $strategy);

        $this->assertSame($strategy, $repository->getFailureStrategy('queue'));
    }

    public function testGetDefaultStrategy()
    {
        $config = m::mock(Configuration::class)
            ->shouldReceive('getFailureStrategyName')
            ->andReturn('unknown strategy name')
            ->getMock();

        $repository = $this->createRepository($config);
        $fallbackStrategy = $this->getMockBuilder(FailureStrategyInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->addFailureStrategy(
            FailureStrategyCollection::FALLBACK_FAILURE_STRATEGY,
            $fallbackStrategy
        );

        $this->assertSame($fallbackStrategy, $repository->getFailureStrategy('queue'));
    }

    public function testGetLastFallbackStrategy()
    {
        $config = m::mock(Configuration::class)
            ->shouldReceive('getFailureStrategyName')
            ->andReturn('unknown strategy name')
            ->getMock();

        $repository = $this->createRepository($config);
        $fallbackStrategy = $this->getMockBuilder(FailureStrategyInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repository->addFailureStrategy('any strategy', $fallbackStrategy);

        $this->assertSame($fallbackStrategy, $repository->getFailureStrategy('queue'));
    }


    private function createRepository($config = null)
    {
        if (is_null($config)) {
            $config = m::mock(Configuration::class)
                ->shouldReceive('getFailureStrategyName')
                ->andReturn(self::FAILURE_STRATEGY_ID)
                ->getMock();
        }

        $repository = new FailureStrategyCollection($config);

        return $repository;
    }
}
