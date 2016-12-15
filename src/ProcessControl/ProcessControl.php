<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\ProcessControl;

/**
 * A PCNTl wrapper that shields you from missing PCNTL or disabled functions
 *
 * Sometimes PCNTL is not required but can be an enhancing option. For those
 * cases this class detects if the PCNTL extension can be used and if not, it
 * returns silently instead of failing loudly with a fatal error.
 *
 * This class can also be injected as a dependency for better testability.
 *
 * @author Martin Schlemmer
 */
class ProcessControl
{
    /**
     * @var bool Does the environment support PCNTL functions?
     */
    private $hasPcntl;

    public function __construct()
    {
        $this->hasPcntl = (
            extension_loaded('pcntl')
            and function_exists('pcntl_signal')
            and function_exists('pcntl_signal_dispatch')
        );
    }

    /**
     * Does the environment support PCNTL functions?
     *
     * @return bool
     */
    public function hasPcntl()
    {
        return $this->hasPcntl;
    }

    /**
     * Register the callback as a handler for all given signals
     *
     * @param int|int[] $signals Signal constant or an array of them
     * @param callable  $callback
     *
     * The $signals argument can either be a single PHP signal constant
     * or an array of signal constants, eg. [SIGINT, SIGTERM].
     */
    public function registerSignalHandler($signals, callable $callback)
    {
        if ($this->hasPcntl === false) {
            return;
        }

        $this->registerHandler($signals, $callback);
    }

    /**
     * Check for unhandled signals and call the handlers if there are any
     *
     * In other words, call the function pcntl_signal_dispatch()
     */
    public function checkForSignals()
    {
        if ($this->hasPcntl === false) {
            return;
        }

        pcntl_signal_dispatch();
    }

    /**
     * Restore the default behavior for given signals
     *
     * @param int|int[] $signals Signal constant or an array of them
     *
     * For the default behavior of each signal
     * @see http://www.manpages.info/linux/signal.7.html
     */
    public function restoreDefaultBehavior($signals)
    {
        if ($this->hasPcntl === false) {
            return;
        }

        $this->registerHandler($signals, SIG_DFL);
    }

    /**
     * Ignore the given signals
     *
     * @param int|int[] $signals Signal constant or an array of them
     */
    public function ignoreSignals($signals)
    {
        if ($this->hasPcntl === false) {
            return;
        }

        $this->registerHandler($signals, SIG_IGN);
    }

    /**
     * Register the handler for the given signals
     *
     * @param int|int[]    $signals
     * @param int|callable $handler
     */
    private function registerHandler($signals, $handler)
    {
        if ( ! is_array($signals)) {
            $signals = array($signals);
        }

        foreach ($signals as $signal) {
            pcntl_signal($signal, $handler);
        }
    }
}
