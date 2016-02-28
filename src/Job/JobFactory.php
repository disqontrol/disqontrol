<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Job;

/**
 * Create new jobs safely
 *
 * There are two situations where one wants to create a new Job. This factory
 * provides two methods that both return a new Job, but they have a different
 * method signature.
 *
 * Creating a Job that is supposed to be sent to Disque needs the job body
 * and the queue name.
 * Creating a Job, that has come FROM Disque and is to be processed, needs
 * all constructor arguments.
 *
 * If you use the methods at their respective places, the method signature
 * will guide you.
 *
 * @author Martin Schlemmer
 */
class JobFactory
{
    /**
     * Create a new job that is supposed to be sent to Disque
     *
     * @param string $jobBody
     * @param string $queue
     *
     * @return JobInterface A new job for Disque
     */
    public function createNewJob($jobBody, $queue)
    {
        return new Job($jobBody, $queue);
    }

    /**
     * @param string $jobBody
     * @param string $queue
     * @param string $jobId
     * @param int    $nacks
     * @param int    $additionalDeliveries
     *
     * @return JobInterface
     */
    public function createJobFromDisque(
        $jobBody,
        $queue,
        $jobId,
        $nacks,
        $additionalDeliveries
    )
    {
        return new Job(
            $jobBody,
            $queue,
            $jobId,
            $nacks,
            $additionalDeliveries
        );
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
    public function cloneFailedJob(JobInterface $job)
    {
        $body = $job->getBodyWithMetadata();
        $queue = $job->getQueue();
        $newJob = $this->createNewJob($body, $queue);

        $originalId = $job->getOriginalId();
        $newJob->setOriginalId($originalId);

        // Because this is a failed job, increment the retry count right here
        $retryCount = $job->getRetryCount() + 1;
        $newJob->setPreviousRetryCount($retryCount);

        return $newJob;
    }
}
