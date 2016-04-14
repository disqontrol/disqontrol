<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer\Process;

use Symfony\Component\Process\Process;

/**
 * A wrapper around the system process of one consumer
 *
 * @author Martin Schlemmer
 */
class ConsumerProcess extends Process
{
    /**
     * @var bool Is the process in burst mode?
     *           Processes in burst mode exit as soon as there is no work to do
     */
    private $isInBurstMode = false;

    /**
     * Set whether this process is in the burst mode, or not
     *
     * @param bool $burstMode
     */
    public function setBurstMode($burstMode)
    {
        $this->isInBurstMode = (bool) $burstMode;
    }

    /**
     * Is this process in burst mode?
     *
     * @return bool
     */
    public function isInBurstMode()
    {
       return $this->isInBurstMode;
    }
}
