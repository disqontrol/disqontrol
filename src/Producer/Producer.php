<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Producer;

use Disqontrol\Disque\AddJob;
use Disqontrol\Event\JobAddBeforeEvent;
use Disqontrol\Event\JobAddAfterEvent;
use Disqontrol\Event\Events;
use Disqontrol\Job\JobInterface;
use Disqontrol\Configuration\Configuration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Producer sends jobs to the queue
 *
 * @author Martin Schlemmer
 */
class Producer implements ProducerInterface
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var AddJob
     */
    private $addJob;

    /**
     * @param Configuration            $config
     * @param EventDispatcherInterface $eventDispatcher
     * @param AddJob                 $jobAdder
     */
    public function __construct(
        Configuration $config,
        EventDispatcherInterface $eventDispatcher,
        AddJob $jobAdder
    ) {
        $this->config = $config;
        $this->eventDispatcher = $eventDispatcher;
        $this->addJob = $jobAdder;
    }

    /**
     * {@inheritdoc}
     */
    public function add(JobInterface $job, $delay = 0)
    {
        $queue = $job->getQueue();
        $jobProcessTimeout = $this->config->getJobProcessTimeout($queue);
        $jobLifetime = $this->config->getJobLifetime($queue);

        // Dispatch a pre-add event
        $preAddEvent = new JobAddBeforeEvent(
            $job,
            $delay,
            $jobProcessTimeout,
            $jobLifetime
        );
        $this->eventDispatcher->dispatch(Events::JOB_ADD_BEFORE, $preAddEvent);

        // Read the Disque call arguments back from the event so that event
        // listeners can change them.
        $jobId = $this->addJob->add(
            $job,
            $preAddEvent->getDelay(),
            $preAddEvent->getJobProcessTimeout(),
            $preAddEvent->getJobLifetime()
        );

        if ($jobId !== false) {
            $result = true;
            $job->setId($jobId);
        } else {
            $result = false;
        }

        // Dispatch a post-add event
        $postAddEvent = new JobAddAfterEvent($job, $result);
        $this->eventDispatcher->dispatch(Events::JOB_ADD_AFTER, $postAddEvent);

        return $result;
    }

}
