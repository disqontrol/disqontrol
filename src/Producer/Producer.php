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

use Disqontrol\Disque\AddJob;
use Disqontrol\Event\JobAddBeforeEvent;
use Disqontrol\Event\JobAddAfterEvent;
use Disqontrol\Event\Events;
use Disqontrol\Job\JobInterface;
use Disqontrol\Configuration\Configuration;
use Disqontrol\Logger\JobLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Disqontrol\Logger\MessageFormatter as Msg;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Configuration            $config
     * @param EventDispatcherInterface $eventDispatcher
     * @param AddJob                   $jobAdder
     * @param LoggerInterface          $logger
     */
    public function __construct(
        Configuration $config,
        EventDispatcherInterface $eventDispatcher,
        AddJob $jobAdder,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->eventDispatcher = $eventDispatcher;
        $this->addJob = $jobAdder;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function add(JobInterface $job, $delay = 0)
    {
        $queue = $job->getQueue();

        $jobProcessTimeout = $this->config->getJobProcessTimeout($queue);
        $jobLifetime = $this->config->getJobLifetime($queue);

        // Set the initial metadata to the new job
        $job->setCreationTime(time());
        $job->setJobLifetime($jobLifetime);
        $job->setProcessTimeout($jobProcessTimeout);

        // Dispatch a pre-add event
        $preAddEvent = new JobAddBeforeEvent($job, $delay);
        $this->eventDispatcher->dispatch(Events::JOB_ADD_BEFORE, $preAddEvent);

        // Read the Disque call arguments back from the job and the event so
        // that event listeners can change them.
        $jobId = $this->addJob->add(
            $job,
            $preAddEvent->getDelay(),
            $job->getProcessTimeout(),
            $job->getJobLifetime()
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

        // Log if the job was added to an undefined queue
        if ( ! $this->isQueueDefined($queue) && $jobId !== false) {
            $this->logger->debug(
                Msg::jobAddedToUndefinedQueue($jobId, $queue)
            );
        }

        return $result;
    }

    /**
     * Check if is the queue defined in the configuration
     *
     * @param string $queue
     *
     * @return bool
     */
    private function isQueueDefined($queue)
    {
        $queuesConfig = $this->config->getQueuesConfig();

        return array_key_exists($queue, $queuesConfig);
    }
}
