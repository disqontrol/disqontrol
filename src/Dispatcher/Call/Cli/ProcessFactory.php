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

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * A factory for new Process objects, mainly for easier testing
 *
 * @author Martin Schlemmer
 */
class ProcessFactory
{
    /**
     * @param string         $commandline The command line to run
     * @param int|float|null $timeout     The timeout in seconds or null to disable
     * @param string|null    $cwd         The working directory or null to use the working dir of the current PHP
     *                                    process
     * @param array|null     $env         The environment variables or null to use the same environment as the current
     *                                    PHP process
     * @param string|null    $input       The input
     * @param array          $options     An array of options for proc_open
     *
     * @return Process
     *
     * @throws RuntimeException When proc_open is not installed
     */
    public function create(
        $commandline,
        $timeout = 60,
        $cwd = null,
        array $env = null,
        $input = null,
        array $options = array()
    ) {
        return new Process($commandline, $cwd, $env, $input, $timeout, $options);
    }

    /**
     * @return NullProcess
     */
    public function createNullProcess()
    {
        return new NullProcess();
    }
}
