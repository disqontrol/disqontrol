<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Failure;

use Disqontrol\Disque\FailJob;

class RetryImmediatelyTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var RetryImmediately
     */ 
    private $retry;
    
    protected function setUp()
    {
        $failJob = $this->getMockBuilder(FailJob::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->retry = new RetryImmediately($failJob);
    }
    
    public function testInstance()
    {
        $this->assertInstanceOf(RetryImmediately::class, $this->retry);
    }
}
