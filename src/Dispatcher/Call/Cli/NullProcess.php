<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Call\Cli;

use Symfony\Component\Process\Process;

/**
 * A process that does nothing and always looks like it failed
 *
 * It fakes the interface of Symfony Process that we use in CliCall.
 * It is used if there's an error in the CliCall constructor.
 *
 * Method comments and docblocks are omitted, because they are useless.
 *
 * @author Martin Schlemmer
 */
class NullProcess extends Process
{
    /**
     * @var int Fake exit code
     */
    private $fakeExitCode = 1;

    public function __construct()
    {
        return;
    }

    public function run($callback = null)
    {
        return;
    }

    public function mustRun(callable $callback = null)
    {
        return;
    }


    public function start(callable $callback = null)
    {
        return;
    }

    public function stop($timeout = 10, $signal = null)
    {
        return;
    }

    public function isRunning()
    {
        return false;
    }

    public function checkTimeout()
    {
        return;
    }

    public function wait(callable $callback = null)
    {
        return $this->getExitCode();
    }

    public function isSuccessful()
    {
        return false;
    }

    public function getExitCode()
    {
        return $this->fakeExitCode;
    }

    public function getOutput()
    {
        return '';
    }

    public function getErrorOutput()
    {
        return '';
    }

    protected function updateStatus($blocking)
    {
        return;
    }
}
