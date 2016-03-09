<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Disque;

use Disqontrol\Configuration\Configuration;
use Disqontrol\Job\JobFactory;
use Disqontrol\Job\JobInterface;
use Disqontrol\Logger\MessageFormatter;
use Disque\Client;
use Psr\Log\LoggerInterface;

/**
 * This class contains various methods for handling a failed job
 *
 * Right now the implemented methods are
 * - NACK (requeue a job)
 * - NACK with a delay
 * - move the job to a failure queue
 *
 * These methods can be used as building blocks by the dispatcher failure strategies
 * to implement concrete failure handling logic.
 *
 * As of 03/2016 it's not possible to delay a NACK or move a job to another queue
 * in Disque. We simulate these commands until they're implemented natively and
 * hide the implementation behind method calls.
 *
 * @see https://github.com/antirez/disque/issues/170
 *      https://github.com/antirez/disque/issues/174
 *
 * @author Martin Schlemmer
 */
class FailJob {
    /**
     * The Disque client for the manipulation of jobs
     *
     * @var Client
     */
    protected $disque;

    /**
     * A class for adding jobs to Disque
     *
     * @var AddJob
     */
    protected $addJob;

    /**
     * @var JobFactory
     */
    protected $jobFactory;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Client          $disque
     * @param AddJob          $addJob
     * @param JobFactory      $jobFactory
     * @param Configuration   $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        Client $disque,
        AddJob $addJob,
        JobFactory $jobFactory,
        Configuration $config,
        LoggerInterface $logger
    ) {
        $this->disque = $disque;
        $this->addJob = $addJob;
        $this->jobFactory = $jobFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param JobInterface $job
     * @param int          $delay in seconds
     *
     * @return bool
     */
    public function nack(JobInterface $job, $delay = 0)
    {
        if ($delay === 0) {
            $this->disque->nack($job->getId());
            return;
        }

        $lifetime = $this->calculateRequeuedJobLifetime($job);

        // The time for this job is up. No more retries
        if ($lifetime <= $delay or $lifetime <= 0) {
            return $this->moveToFailureQueue($job);
        }

        // Return back to the same queue
        $queue = $job->getQueue();

        return $this->move($job, $lifetime, $queue, $delay);

    }

    public function moveToFailureQueue(JobInterface $job)
    {
        $queue = $job->getQueue();
        $failureQueue = $this->config->getFailureQueue($queue);
        $delay = 0;
        $result = $this->move(
            $job,
            Configuration::MAX_ALLOWED_JOB_LIFETIME,
            $failureQueue,
            $delay
        );

        // This is a critical error, we have lost the job
        if ($result === false) {
            $this->logger->critical(
                MessageFormatter::failedToMoveJobToFailureQueue(
                    $job->getId(),
                    $queue,
                    $failureQueue
                )
            );
        }
    }

    /**
     * Move the job to a new queue
     *
     * @param JobInterface $job
     * @param int          $jobLifetime
     * @param string       $newQueue
     * @param int          $delay
     *
     * @return bool
     */
    private function move(
        JobInterface $job,
        $jobLifetime,
        $newQueue,
        $delay
    ) {
        // Create the job's copy
        $newJob = $this->jobFactory->cloneFailedJob($job);

        // Set the new queue
        $newJob->setQueue($newQueue);

        // Use the original process timeout
        $processTimeout = $job->getProcessTimeout();

        // Add the job copy to the new queue
        return (bool) $this->addJob->add($newJob, $delay, $processTimeout, $jobLifetime);
    }

    /**
     * Calculate the lifetime of a requeued job
     *
     * If a job is requeued, its new lifetime should be shorter by the difference
     * between now and its creation time
     *
     * @param JobInterface $job
     *
     * @return int The rest lifetime
     */
    private function calculateRequeuedJobLifetime(JobInterface $job)
    {
        $newLifetime = $job->getCreationTime() + $job->getJobLifetime() - time();
        return (int)$newLifetime;
    }

}
