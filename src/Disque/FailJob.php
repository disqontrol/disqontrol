<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Disque;

use Disqontrol\Configuration\Configuration;
use Disqontrol\Job\JobFactory;
use Disqontrol\Job\JobInterface;
use Disqontrol\Logger\MessageFormatter as Msg;
use Disque\Client;
use Psr\Log\LoggerInterface;
use Exception;
use Disqontrol\Logger\JobLogger;

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
     * 3. And if the job's lifetime is up or if it has no more retries left,
     *    it moves the job to its failure queue instead of NACKing it. Thus no
     *    job stays in the queue longer than the client wishes it to stay.
     *
     * @param JobInterface $job
     * @param int          $delay in seconds
     *
     * @return bool
     */
    public function nack(JobInterface $job, $delay = 0)
    {
        if ($this->jobHasReachedRetryLimit($job)
            || $this->jobLifetimeIsUp($job, $delay)) {
            
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
                Msg::failedToMoveJobToFailureQueue($jobId, $queue, $failureQueue, $originalId)
            );

        } else {
            $this->logger->info(
                Msg::movedJobToFailureQueue($jobId, $queue, $failureQueue, $originalId)
            );
        }

        return $movedToFailureQueue;
    }

    /**
     * Log an error about a failed job
     *
     * @param JobInterface $job
     * @param string       $errorMessage
     */
    public function logError(JobInterface $job, $errorMessage = '')
    {
        $context[JobLogger::JOB_INDEX] = $job;
        $this->logger->error(
            Msg::failedProcessJob(
                $job->getId(),
                $job->getQueue(),
                $errorMessage,
                $job->getOriginalId()
            ),
            $context
        );
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
            // queue (where it is reserved right now), once in the new queue.
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
            // wrong to have a job twice than to lose it altogether.
            // Just log it so the inconsistency can be explained.
            $this->logger->error(
                Msg::failedToRemoveJobFromSourceQueue($jobId, $job->getQueue(), $newQueue, $job->getOriginalId())
            );
        }

        // Create the job's copy
        $newJob = $this->cloneFailedJob($job);

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
     * @return int The remaining lifetime
     */
    private function calculateRemainingLifetime(JobInterface $job)
    {
        $jobLifetime = (int)$job->getJobLifetime();
        
        if ($job->getCreationTime() === null) {
            return $jobLifetime;
        }
    
        $creationTime = (int)$job->getCreationTime();

        $remainingLifetime = $creationTime + $jobLifetime - time();

        return $remainingLifetime;
    }

    /**
     * Check if the job has reached its max retry limit
     *
     * @param JobInterface $job
     *
     * @return bool
     */
    private function jobHasReachedRetryLimit(JobInterface $job)
    {
        $queue = $job->getQueue();

        $maxRetries = $this->config->getMaxRetries($queue);
        $retryCount = $job->getRetryCount();

        $jobHasReachedRetryLimit = $maxRetries <= $retryCount;

        if ($jobHasReachedRetryLimit) {
            $this->logger->debug(
                Msg::jobReachedRetryLimit($job->getId(), $maxRetries, $job->getOriginalId())
            );
        }

        return $jobHasReachedRetryLimit;
    }

    /**
     * Check if the job still has a lifetime left or not
     *
     * @param JobInterface $job
     * @param int          $delay Planned job delay
     *
     * @return bool True - the job is out of time for the next retry
     */
    private function jobLifetimeIsUp(JobInterface $job, $delay)
    {
        $remainingLifetime = $this->calculateRemainingLifetime($job);

        $lifetimeIsUp = ($remainingLifetime <= $delay || $remainingLifetime <= 0);

        if ($lifetimeIsUp) {
            $this->logger->debug(
                Msg::jobOutOfTime($job->getId(), $job->getJobLifetime(), $job->getOriginalId())
            );
        }

        return $lifetimeIsUp;
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
                Msg::failedToNack($jobId, $queue, $e->getMessage(), $job->getProcessTimeout(), $originalId)
            );

            return false;
        }

        $delay = 0;
        $this->logger->info(Msg::jobNacked($jobId, $queue, $delay, $originalId));

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
                Msg::failedToNack($jobId, $queue, $message, $job->getProcessTimeout(), $originalId)
            );
        } else {
            $this->logger->info(Msg::jobNacked($jobId, $queue, $delay, $originalId));
        }

        return $nackedWithDelay;
    }

    /**
     * Create a clone of an existing job, remembering its ID and incrementing
     * the retry count by 1
     *
     * This is used to work around the limitations of Disque which cannot
     * move jobs to a different queue, NACK them with a delay or change the job
     * body.
     *
     * In all these situations, we create a new job and store the original job ID
     * and the retry count in the job metadata.
     *
     * @see https://github.com/antirez/disque/issues/174
     *
     * @param JobInterface $job
     *
     * @return JobInterface A new job ready to be inserted in Disque
     */
    private function cloneFailedJob(JobInterface $job)
    {
        $body = $job->getBodyWithMetadata();
        $queue = $job->getQueue();
        $newJob = $this->jobFactory->createNewJob($body, $queue);

        $originalId = $job->getOriginalId();
        $newJob->setOriginalId($originalId);

        // Because this is a failed job, increment the retry count right here
        $retryCount = $job->getRetryCount() + 1;
        $newJob->setPreviousRetryCount($retryCount);

        return $newJob;
    }

}
