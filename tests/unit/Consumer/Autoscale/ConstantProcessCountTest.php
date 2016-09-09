<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer\Autoscale;

class ConstantProcessCountTest extends \PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $cpc = $this->createConstantProcessCount();
        $this->assertInstanceOf(ConstantProcessCount::class, $cpc);
    }

    public function testReturnsTheSameCount()
    {
        $count = 123;
        $cpc = $this->createConstantProcessCount($count);

        $dummy = -100; // Doesn't matter
        $this->assertEquals($count, $cpc->calculateProcessCount($dummy));
    }

    public function testAtLeastOne()
    {
        $wrongCount = -1;
        $cpc = $this->createConstantProcessCount($wrongCount);

        $dummy = -100; // Doesn't matter
        $expectedCount = 1;
        $this->assertEquals($expectedCount, $cpc->calculateProcessCount($dummy));
    }

    private function createConstantProcessCount($count = 1)
    {
        return new ConstantProcessCount($count);
    }
}
