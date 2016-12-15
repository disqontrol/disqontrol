<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Worker;

use Disqontrol\Console\Command\WorkerCommand;
use Disqontrol\Dispatcher\Call\Factory\CallFactoryInterface;
use Disqontrol\Job\Job;
use Disqontrol\Job\JobInterface;
use Disqontrol\Job\Serializer\SerializerInterface;
use Disqontrol\Router\WorkerDirections;
use Psr\Log\LoggerInterface;
use Disqontrol\Logger\MessageFormatter as msg;

/**
 * A helper that wraps a PHP worker called in a separate process
 *
 * If the user wants to call a PHP worker in a separate process, Disqontrol
 * takes care of both calling and executing the PHP worker. This is the executing
 * part, invoked through the command line.
 *
 * This class calls the isolated_php_worker inline; this is the point where it
 * is isolated in a separate process.
 *
 * @see    WorkerCommand
 *
 * @author Martin Schlemmer
 */
class PhpWorkerExecutor
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var CallFactoryInterface
     */
    private $phpCallFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SerializerInterface  $serializer
     * @param CallFactoryInterface $phpCallFactory
     * @param LoggerInterface      $logger
     */
    public function __construct(
        SerializerInterface $serializer,
        CallFactoryInterface $phpCallFactory,
        LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->phpCallFactory = $phpCallFactory;
        $this->logger = $logger;
    }

    /**
     * Process the job by the given worker
     *
     * @param string $workerName
     * @param string $jobQueue
     * @param string $jobBody
     * @param string $jobMetadata
     *
     * @return bool
     */
    public function process($workerName, $jobQueue, $jobBody, $jobMetadata)
    {
        $job = $this->createJob($jobQueue, $jobBody, $jobMetadata);
        $result = $this->callWorker($job, $workerName);

        return $result;
    }

    /**
     * Create a job from the command line arguments
     *
     * @param string $jobQueue
     * @param string $jobBody
     * @param string $jobMetadata
     *
     * @return JobInterface
     */
    private function createJob($jobQueue, $jobBody, $jobMetadata)
    {
        // We must deserialize the data from the command line.
        // This is the opposite of what happens in the caller,
        // CliCall::__construct()
        $jobBody = $this->serializer->deserialize($jobBody);
        $jobMetadata = $this->serializer->deserialize($jobMetadata);

        $bodyWithMetadata = [
            Job::KEY_BODY => $jobBody,
            Job::KEY_METADATA => $jobMetadata
        ];

        $job = new Job($bodyWithMetadata, $jobQueue);

        return $job;
    }

    /**
     * Tell the PHP worker to process the job and return the result
     *
     * @param JobInterface $job
     * @param string       $workerName
     *
     * @return bool Did the worker process the job successfully?
     */
    private function callWorker(JobInterface $job, $workerName)
    {
        $directions = new WorkerDirections(
            WorkerType::INLINE_PHP_WORKER(),
            $workerName
        );

        // We want to call the PHP worker here and now, so we skip
        // the Dispatcher, JobRouter and CalLFactory and go directly for
        // the PhpCallFactory.
        $call = $this->phpCallFactory->createCall($directions, $job);
        $call->call();
        
        if ( ! $call->wasSuccessful() && ! empty($call->getErrorMessage())) {
            // Note at this point we don't have the current, only the original
            // job ID. Hopefully the log entry will be understandable.
            $this->logger->error(
                msg::isolatedPhpWorkerFailed($workerName, $job->getOriginalId(), $call->getErrorMessage())
            );
        }

        return $call->wasSuccessful();
    }
}
