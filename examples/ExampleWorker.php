<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

use Disqontrol\Job\JobInterface;
use Disqontrol\Worker\WorkerInterface;

/**
 * This is an example PHP worker
 *
 * @see examples/disqontrol_bootstrap.php
 */
class ExampleWorker implements WorkerInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(JobInterface $job)
    {
        echo sprintf(
            'Job from the queue "%s" successfully processed',
            $job->getQueue()
        );

        return true;
    }
}
