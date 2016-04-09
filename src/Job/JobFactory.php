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

use Disqontrol\Configuration\Configuration;
use Psr\Log\LoggerInterface;
use Disqontrol\Logger\MessageFormatter as Msg;

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
     * @var Configuration
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Configuration $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

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
        $job = new Job($jobBody, $queue);

        if ($this->jobHasBeenAddedFromOutside($job)) {
            $this->addMissingJobTimeData($job);
        }

        return $job;
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
    ) {
        $job = new Job(
            $jobBody,
            $queue,
            $jobId,
            $nacks,
            $additionalDeliveries
        );

        if ($this->jobHasBeenAddedFromOutside($job)) {
            $this->addMissingJobTimeData($job);
        }

        return $job;
    }

    /**
     * Check if the job has been added from outside of Disqontrol
     *
     * Jobs added from the outside don't have time information - job creation
     * time and job lifetime.
     *
     * @param JobInterface $job
     *
     * @return bool
     */
    private function jobHasBeenAddedFromOutside(JobInterface $job)
    {
        $jobHasNoLifetime = empty($job->getJobLifetime());
        $jobHasNoCreationTime = empty($job->getCreationTime());

        return ($jobHasNoLifetime and $jobHasNoCreationTime);
    }

    /**
     * Add missing job lifetime and job creation time
     *
     * If the job has been added through other means than the Disqontrol
     * producer, it is missing time metadata. Add it here.
     *
     * This is a mirror of the process in the producer
     * @see Disqontrol\Producer\Producer::add()
     *
     * @param JobInterface $job
     */
    private function addMissingJobTimeData(JobInterface $job)
    {
        $queue = $job->getQueue();
        $jobLifetime = $this->config->getJobLifetime($queue);
        $creationTime = time();

        $job->setCreationTime($creationTime);
        $job->setJobLifetime($jobLifetime);

        $this->logger->debug(
            Msg::addedJobTimeData($job->getId(), $creationTime, $jobLifetime, $job->getOriginalId())
        );
    }
}
