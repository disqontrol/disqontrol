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

use Disqontrol\Event\JobAddBeforeEvent;
use Disqontrol\Event\JobAddAfterEvent;
use Disqontrol\Event\Events;
use Disqontrol\Job\JobInterface;
use Disqontrol\Configuration\Configuration;
use Disqontrol\Job\Marshaller\MarshallerInterface;
use Disque\Client;
use Disque\Connection\Response\ResponseException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use RuntimeException;

/**
 * Producer sends jobs to the queue
 *
 * @author Martin Schlemmer
 */
class Producer implements ProducerInterface
{
    /**
     * @var string Constants for the disque-php method Client::AddJob()
     */
    const DISQUE_ADDJOB_DELAY = 'delay';
    const DISQUE_ADDJOB_MAX_JOB_PROCESS_TIME = 'retry';
    const DISQUE_ADDJOB_MAX_JOB_LIFETIME = 'ttl';

    /**
     * @var Client A client for communicating with Disque
     */
    private $disque;

    /**
     * @var MarshallerInterface Serializer for the job body
     */
    private $marshaller;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var
     */
    private $eventDispatcher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Client              $disque
     * @param MarshallerInterface $marshaller
     * @param Configuration       $config
     * @param LoggerInterface     $logger
     * @param EventDispatcher     $eventDispatcher
     */
    public function __construct(
        Client $disque,
        MarshallerInterface $marshaller,
        Configuration $config,
        LoggerInterface $logger,
        EventDispatcher $eventDispatcher
    )
    {
        $this->disque = $disque;
        $this->marshaller = $marshaller;
        $this->config = $config;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function add(JobInterface $job, $delay = 0)
    {
        $queue = $job->getQueue();
        $maxJobProcessTime = $this->config->getMaxJobProcessTime($queue);
        $maxJobLifetime = $this->config->getMaxJobLifetime($queue);

        // Dispatch a pre-add event
        $preAddEvent = new JobAddBeforeEvent(
            $job,
            $delay,
            $maxJobProcessTime,
            $maxJobLifetime
        );
        $this->eventDispatcher->dispatch(Events::JOB_ADD_BEFORE, $preAddEvent);

        // Read the Disque call arguments back from the event so that event
        // listeners can change them.
        $jobId = $this->doAdd(
            $job,
            $preAddEvent->getDelay(),
            $preAddEvent->getMaxJobProcessTime(),
            $preAddEvent->getMaxJobLifetime()
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

    /**
     * Send a job to Disque
     *
     * @param JobInterface $job               The job to add
     * @param int          $delay             Job delay in seconds
     * @param int          $maxJobProcessTime Maximum job process time
     * @param int          $maxJobLifetime    Maximum job lifetime
     *
     * @return string|bool Job ID The ID assigned to the job by Disque, or false
     */
    private function doAdd(
        JobInterface $job,
        $delay,
        $maxJobProcessTime,
        $maxJobLifetime
    )
    {
        $options = [
            self::DISQUE_ADDJOB_DELAY => $delay,
            self::DISQUE_ADDJOB_MAX_JOB_PROCESS_TIME => $maxJobProcessTime,
            self::DISQUE_ADDJOB_MAX_JOB_LIFETIME => $maxJobLifetime
        ];

        try {
            $jobBody = $this->marshaller->marshal($job->getBodyWithMetadata());
        } catch (RuntimeException $e) {
            $this->logger->error($e->getMessage());
            return false;
        }

        $queue = $job->getQueue();

        try {
            $jobId = $this->disque->addJob(
                $queue,
                $jobBody,
                $options
            );

            $this->logger->info(
                sprintf('Added a job %s to the queue %s', $jobId, $queue)
            );

        } catch (ResponseException $e) {
            $jobId = false;

            $this->logger->error($e->getMessage());
            $this->logger->debug(
                sprintf('Body of the not added job: %s', $jobBody)
            );
        }

        return $jobId;
    }
}
