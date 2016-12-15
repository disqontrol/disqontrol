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

/**
 * Console output with varying verbosity levels
 *
 * The ConsoleOutput accepts Symfony verbosity levels:
 *
 * @see and use constants in \Symfony\Component\Console\Output\OutputInterface
 *
 * It has three methods for console output, each meant for a different verbosity
 * level. All methods return an instance of SymfonyStyle for easy text
 * formatting.
 *
 * @author Martin Schlemmer
 */
interface ConsoleOutputInterface
{
    /**
     * Output a "Normal" verbosity level message to the console
     *
     * @return \Symfony\Component\Console\Style\SymfonyStyle
     */
    public function normal();

    /**
     * Output a "Verbose" verbosity level message to the console
     *
     * @return \Symfony\Component\Console\Style\SymfonyStyle
     */
    public function verbose();

    /**
     * Output a "Debug" verbosity level message to the console
     *
     * @return \Symfony\Component\Console\Style\SymfonyStyle
     */
    public function debug();
}
