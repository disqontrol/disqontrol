<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer;

/**
 * A long-running process that listens to the queue and processes new jobs
 * 
 * @author Martin Schlemmer
 */
interface ConsumerInterface
{
    /**
     * Start listening to the queues
     *
     * @param array $queueNames Names of the queues to listen to
     */
    public function listen(array $queueNames);
}
