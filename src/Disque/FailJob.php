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
use Disqontrol\Logger\MessageFormatter as msg;
use Disque\Client;
use Psr\Log\LoggerInterface;
use Exception;

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
 * we hide the implementation behind method calls.
 *
 * @see https://github.com/antirez/disque/issues/170
 *      https://github.com/antirez/disque/issues/174
 *
 * @author Martin Schlemmer
 */
class FailJob
{
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
     * NACK the job with or without delay
     *
     * This method does three things.
     * 1. It NACKs the job, which means it tells Disque that the job failed
     *    and it should be requeued immediately.
     * 2. It can also NACK a job with a delay.
     * 3. And if the job's lifetime is up, it moves the job to its failure queue
     *    instead of NACKing it. Thus no job stays in the queue longer than
     *    the client wishes it to stay.
     *
     * @param JobInterface $job
     * @param int          $delay in seconds
     *
     * @return bool
     */
    public function nack(JobInterface $job, $delay = 0)
    {
        $remainingLifetime = $this->calculateRemainingLifetime($job);

        // The time for this job is up. No more retries
        if ($remainingLifetime <= $delay or $remainingLifetime <= 0) {

            return $this->moveToFailureQueue($job);
        }

        if ($delay === 0) {

            return $this->nackImmediately($job);
        }

        return $this->nackWithDelay($job, $delay);
    }

    /**
     * Move the job to its failure queue
     *
     * @param JobInterface $job
     *
     * @return bool
     */
    public function moveToFailureQueue(JobInterface $job)
    {
        $queue = $job->getQueue();
        $failureQueue = $this->config->getFailureQueue($queue);
        $lifetime = Configuration::MAX_ALLOWED_JOB_LIFETIME;
        $delay = 0;

        $movedToFailureQueue = $this->move($job, $lifetime, $failureQueue, $delay);

        $jobId = $job->getId();
        $originalId = $job->getOriginalId();

        // This is a critical error. Not only has the job failed, we could not
        // move it to the failure queue. It's ACKed in its original queue and
        // doesn't exist in the failure queue. The job is lost.
        if ($movedToFailureQueue === false) {
            $this->logger->critical(
                msg::failedToMoveJobToFailureQueue($jobId, $queue, $failureQueue, $originalId)
            );

        } else {
            $this->logger->info(
                msg::movedJobToFailureQueue($jobId, $queue, $failureQueue, $originalId)
            );
        }

        return $movedToFailureQueue;
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
    private function move(JobInterface $job, $jobLifetime, $newQueue, $delay)
    {
        $jobId = $job->getId();

        try {
            // ACK the job in its original queue to remove it
            $this->disque->ackJob($jobId);
        } catch (Exception $e) {
            // What should we do if the ACK fails? Should we go on, or stop?
            // If we go on, the job will exist twice, once in the original
            // queue (right now it is reserved), once in the new queue.
            //
            // What happens next depends on its lifetime and process timeout.
            // It might be requeued and processed again if the process timeout
            // comes sooner that the lifetime end.
            // It might be removed, if the lifetime ends sooner than the process
            // timeout.
            //
            // The worst case scenario in continuing is that the job exists
            // in a new queue but it has been processed successfully in the old
            // queue.
            //
            // The worst case scenario in stopping here is that the job
            // disappears altogether.
            //
            // Based on this I think we should go on and move the job. It's less
            // bad to have a job twice than to lose it altogether.
            // Just log it so the inconsistency can be explained.
            $this->logger->error(
                msg::failedToRemoveJobFromSourceQueue($jobId, $job->getQueue(), $newQueue, $job->getOriginalId())
            );
        }

        // Create the job's copy
        $newJob = $this->jobFactory->cloneFailedJob($job);

        // Set the new queue
        $newJob->setQueue($newQueue);

        // Always use the original process timeout
        $processTimeout = $job->getProcessTimeout();

        // Add the job copy to the new queue
        return (bool)$this->addJob->add($newJob, $delay, $processTimeout, $jobLifetime);
    }

    /**
     * Calculate the remaining lifetime of a requeued job
     *
     * If a job is requeued, its new lifetime should be shorter by the difference
     * between now and its creation time
     *
     * @param JobInterface $job
     *
     * @return int The rest lifetime
     */
    private function calculateRemainingLifetime(JobInterface $job)
    {
        $creationTime = $job->getCreationTime();
        $jobLifetime = (int)$job->getJobLifetime();

        if (empty($creationTime)) {
            return $jobLifetime;
        }

        $remainingLifetime = $creationTime + $jobLifetime - time();

        return $remainingLifetime;
    }

    /**
     * NACK the job immediately
     *
     * @param JobInterface $job
     *
     * @return bool
     */
    private function nackImmediately(JobInterface $job)
    {
        $queue = $job->getQueue();
        $jobId = $job->getId();
        $originalId = $job->getOriginalId();

        try {
            $this->disque->nack($jobId);
        } catch (Exception $e) {
            $this->logger->error(
                msg::failedToNack($jobId, $queue, $e->getMessage(), $job->getProcessTimeout(), $originalId)
            );

            return false;
        }

        $this->logger->info(msg::jobNacked($jobId, $queue, $originalId));

        return true;
    }

    /**
     * NACK the job with a delay
     *
     * @param JobInterface $job
     * @param int          $delay in seconds
     *
     * @return bool
     */
    private function nackWithDelay(JobInterface $job, $delay)
    {
        $queue = $job->getQueue();
        $remainingLifetime = $this->calculateRemainingLifetime($job);

        // Return back to the same queue
        $nackedWithDelay = $this->move($job, $remainingLifetime, $queue, $delay);

        $jobId = $job->getId();
        $originalId = $job->getOriginalId();

        if ($nackedWithDelay === false) {
            $message = '';
            $this->logger->error(
                msg::failedToNack($jobId, $queue, $message, $job->getProcessTimeout(), $originalId)
            );
        } else {
            $this->logger->info(msg::jobNacked($jobId, $queue, $originalId));
        }

        return $nackedWithDelay;
    }

}