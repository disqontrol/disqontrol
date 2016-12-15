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

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * {@see ConsoleOutputInterface}
 *
 * @author Martin Schlemmer
 */
class ConsoleOutput implements ConsoleOutputInterface
{
    /**
     * These are instances of SymfonyStyle with properly set outputs.
     *
     * If messages for the given verbosity level should be discarded,
     * SymfonyStyle will be instantiated with NullOutput to discard them.
     *
     * @var SymfonyStyle
     */
    private $normalOutput;
    private $verboseOutput;
    private $debugOutput;

    /**
     * @param OutputInterface $output with a properly set verbosity level
     */
    public function __construct(OutputInterface $output)
    {
        $input = new ArrayInput(array());
        $loudOutput = new SymfonyStyle($input, $output);

        $nullOutput = new NullOutput();
        $quietOutput = new SymfonyStyle($input, $nullOutput);

        $verbosity = $output->getVerbosity();

        $this->normalOutput = $verbosity < OutputInterface::VERBOSITY_NORMAL
            ? $quietOutput : $loudOutput;

        $this->verboseOutput = $verbosity < OutputInterface::VERBOSITY_VERBOSE
            ? $quietOutput : $loudOutput;

        $this->debugOutput = $verbosity < OutputInterface::VERBOSITY_DEBUG
            ? $quietOutput : $loudOutput;
    }

    /**
     * {@inheritdoc}
     */
    public function normal()
    {
        return $this->normalOutput;
    }

    /**
     * {@inheritdoc}
     */
    public function verbose()
    {
        return $this->verboseOutput;
    }

    /**
     * {@inheritdoc}
     */
    public function debug()
    {
        return $this->debugOutput;
    }
}
