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

use Psr\Log\NullLogger;

class LogAndThrowAwayTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var LogAndThrowAway
     */ 
    private $strategy;
    
    protected function setUp()
    {
        $logger = new NullLogger();
        $this->strategy = new LogAndThrowAway($logger);
    }
    
    public function testInstance()
    {
        $this->assertInstanceOf(LogAndThrowAway::class, $this->strategy);
    }
}
