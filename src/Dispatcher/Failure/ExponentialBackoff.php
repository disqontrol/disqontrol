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


use Vivait\Backoff\Strategies\ExponentialBackoffStrategy;

/**
 * Calculate the exponential backoff for a failed job
 *
 * Each next retry has a higher delay than the previous one. The delay grows
 * exponentially.
 *
 * For more details @see RetryWithExponentialBackoff
 *
 * @author Martin Schlemmer
 */
class ExponentialBackoff extends ExponentialBackoffStrategy
{
    /**
     * Max delay in seconds. ~ 25 hours
     */
    const MAX_BACKOFF = 90000;

    /**
     * The base of the exponential growth
     */
    const BASE_NUMBER = 4;

    /**
     * @param float $jitterMultiplier
     * @param int   $maxBackoff
     * @param int   $minBackoff
     * @param int   $maxRetries
     */
    public function __construct(
        $jitterMultiplier = 0.1,
        $maxBackoff = 15552000,
        $minBackoff = 1,
        $maxRetries = null
    ) {
        parent::__construct($jitterMultiplier, $maxBackoff, $minBackoff, $maxRetries);
        $this->setBaseNumber(self::BASE_NUMBER);
        $this->setMaxBackoff(self::MAX_BACKOFF);
    }
}
