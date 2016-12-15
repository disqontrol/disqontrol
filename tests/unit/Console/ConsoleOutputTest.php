<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Console;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput as SfConsoleOutput;

class ConsoleOutputTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalVerbosity()
    {
        $systemVerbosity = OutputInterface::VERBOSITY_NORMAL;
        $normalVisible = true;
        $verboseVisible = false;
        $debugVisible = false;

        $this->checkVerbosity($systemVerbosity, $normalVisible, $verboseVisible, $debugVisible);
    }

    public function testQuietVerbosity()
    {
        $systemVerbosity = OutputInterface::VERBOSITY_QUIET;
        $normalVisible = false;
        $verboseVisible = false;
        $debugVisible = false;

        $this->checkVerbosity($systemVerbosity, $normalVisible, $verboseVisible, $debugVisible);
    }

    public function testVerboseVerbosity()
    {
        $systemVerbosity = OutputInterface::VERBOSITY_VERBOSE;
        $normalVisible = true;
        $verboseVisible = true;
        $debugVisible = false;

        $this->checkVerbosity($systemVerbosity, $normalVisible, $verboseVisible, $debugVisible);
    }

    public function testDebugVerbosity()
    {
        $systemVerbosity = OutputInterface::VERBOSITY_DEBUG;
        $normalVisible = true;
        $verboseVisible = true;
        $debugVisible = true;

        $this->checkVerbosity($systemVerbosity, $normalVisible, $verboseVisible, $debugVisible);
    }

    /**
     * @param int  $verbosity      Symfony verbosity constant
     * @param bool $normalVisible  Should normal messages be visible?
     * @param bool $verboseVisible Should verbose messages be visible?
     * @param bool $debugVisible   Should debug messages be visible?
     */
    private function checkVerbosity(
        $verbosity,
        $normalVisible,
        $verboseVisible,
        $debugVisible)
    {
        $sfOutput = new SfConsoleOutput($verbosity);
        $output = new ConsoleOutput($sfOutput);

        $expectedNormalVerbosity = $normalVisible ? $verbosity : OutputInterface::VERBOSITY_QUIET;
        $actualNormalVerbosity = $output->normal()->getVerbosity();
        $this->assertSame($expectedNormalVerbosity, $actualNormalVerbosity);

        $expectedVerboseVerbosity = $verboseVisible ? $verbosity : OutputInterface::VERBOSITY_QUIET;
        $actualVerboseVerbosity = $output->verbose()->getVerbosity();
        $this->assertSame($expectedVerboseVerbosity, $actualVerboseVerbosity);

        $expectedDebugVerbosity = $debugVisible ? $verbosity : OutputInterface::VERBOSITY_QUIET;
        $actualDebugVerbosity = $output->debug()->getVerbosity();
        $this->assertSame($expectedDebugVerbosity, $actualDebugVerbosity);
    }
}
