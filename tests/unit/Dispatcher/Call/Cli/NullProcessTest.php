<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Call\Cli;

class NullProcessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NullProcess
     */
    private $np;

    protected function setUp()
    {
        $this->np = new NullProcess();
    }

    public function testInstance()
    {
        $this->assertInstanceOf(NullProcess::class, $this->np);
    }

    /**
     * There are no assertions here. We just want to test that we can call
     * the basic methods without fatal errors or thrown exceptions.
     */
    public function testBasicCalls()
    {
        $this->np->start();
        $this->np->run();
        $this->np->mustRun();
        $this->np->stop();
        $this->np->wait();
        $this->np->getExitCode();
        $this->np->checkTimeout();
        $this->np->getPid();
    }

    public function testAlwaysFailure()
    {
        $this->assertFalse($this->np->isSuccessful());
    }

    public function testAlwaysStopped()
    {
        $this->assertFalse($this->np->isRunning());
    }
}
