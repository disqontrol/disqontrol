<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Producer;

use Disqontrol\Dispatcher\JobDispatcherInterface;
use Disqontrol\Job\JobInterface;

/**
 * A synchronous producer processes the job immediately instead of enqueueing it
 *
 * The normal job producer sends the job to Disque. This synchronous producer
 * skips Disque and instead processes the job immediately.
 *
 * If the job fails, the failure is logged and the job is thrown away.
 * Failed jobs in a synchronous producer are not repeated.
 *
 * @author Martin Schlemmer
 */
class SynchronousProducer implements ProducerInterface
{
    /**
     * @var JobDispatcherInterface
     */
    private $jobDispatcher;

    /**
     * @param JobDispatcherInterface $jobDispatcher
     */
    public function __construct(JobDispatcherInterface $jobDispatcher)
    {
        $this->jobDispatcher = $jobDispatcher;
    }

    /**
     * The delay parameter is ignored.
     *
     * The job doesn't receive any job ID, because it doesn't reach Disque.
     * The return value is the result of the job processing.
     *
     * {@inheritdoc}
     */
    public function add(JobInterface $job, $delay = 0)
    {
        return $this->jobDispatcher->dispatch([$job]);
    }
}
