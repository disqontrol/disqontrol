<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher;

use Disqontrol\Dispatcher\Failure\FailureStrategyCollection;
use Disqontrol\Logger\JobLogger;
use Disqontrol\Logger\MessageFormatter;
use Disqontrol\Job\JobInterface;
use Disqontrol\ProcessControl\ProcessControl;
use Disqontrol\Router\JobRouterInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Disqontrol\Exception\JobRouterException;
use Disqontrol\Dispatcher\Call\CallInterface;
use Disque\Client;

/**
 * Job dispatcher sends jobs to the proper workers and handles errors.
 *
 * The job dispatcher takes care of the whole job processing.
 * It has three stages:
 *
 * 1. Collect calls. Ask the job router where the worker for each job is and
 *   how it should be called.
 *
 *   The job router asks each of its routes if it can decide about the job and
 *   expects a Call object from them. These Calls are what the job dispatcher
 *   collects in the first stage.
 *
 * 2. Call each worker. If there are more calls, they can be dispatched
 *   in parallel depending on the type of the worker.
 *
 * 3. Handle the results, or more precisely, handle the errors that come up.
 *   A successfully processed job doesn't need anything else, but jobs that
 *   ended with a failure need to be requeued, logged and/or deleted.
 *
 *   To handle a failed job, the job dispatcher asks the FailureStrategyCollection
 *   for the proper failure strategy. Failure strategy is an exact description
 *   of what should happen to a failed job. It then delegates the failure
 *   handling to the strategy, without caring what exactly happens.
 *
 *   Successful jobs are simply removed from Disque (ACKed).
 *
 * @author Martin Schlemmer
 */
class JobDispatcher implements JobDispatcherInterface
{
    /**
     * How long to wait in the main loop, waiting for calls to return
     * 10000 microseconds = 0.01s
     */
    const LOOP_PAUSE = 10000;

    /**
     * Helper constants for ordering the calls
     */
    const NON_BLOCKING = 0;
    const BLOCKING = 1;

    /**
     * JobRouter decides what worker is responsible for each job and how it
     * should be called.
     *
     * @var JobRouterInterface
     */
    private $jobRouter;

    /**
     * The Disque client can ACK a successful job
     *
     * @var Client
     */
    private $disque;

    /**
     * A collection of failure strategies
     *
     * @var FailureStrategyCollection
     */
    private $failureStrategies;

    /**
     * @var ProcessControl
     */
    private $processControl;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool Terminate the current job batch
     *           No new calls will be started
     */
    private $mustTerminate = false;

    /**
     * @param JobRouterInterface        $jobRouter
     * @param Client                    $disque
     * @param FailureStrategyCollection $failureStrategies
     * @param ProcessControl            $processControl
     * @param LoggerInterface           $logger
     */
    public function __construct(
        JobRouterInterface $jobRouter,
        Client $disque,
        FailureStrategyCollection $failureStrategies,
        ProcessControl $processControl,
        LoggerInterface $logger
    ) {
        $this->jobRouter = $jobRouter;
        $this->disque = $disque;
        $this->failureStrategies = $failureStrategies;
        $this->processControl = $processControl;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(array $jobs)
    {
        $calls = $this->collectCalls($jobs);
        $this->callWorkers($calls);
    }

    /**
     * {@inheritdoc}
     *
     * The job dispatcher will gracefully stop what it's doing and exit.
     * @see JobDispatcher::startCalls()
     */
    public function terminate()
    {
        $this->mustTerminate = true;
    }

    /**
     * Ask the job router for calls for given jobs
     *
     * Calls are objects that know how to call a worker and how to interpret
     * the returned results.
     *
     * Handle any unroutable jobs here.
     *
     * @param JobInterface[] $jobs
     *
     * @return CallInterface[] Calls
     */
    private function collectCalls(array $jobs)
    {
        $calls = [];
        foreach ($jobs as $job) {
            try {
                $call = $this->jobRouter->getCall($job);

            } catch (JobRouterException $e) {
                $context[JobLogger::JOB_INDEX] = $job;
                $this->logger->error($e->getMessage(), $context);

                continue;
            }

            $calls[] = $call;
        }

        return $calls;
    }

    /**
     * Call the workers
     *
     * @param CallInterface[] $calls
     */
    private function callWorkers(array $calls)
    {
        $calls = $this->orderCalls($calls);
        $startedCalls = $this->startCalls($calls);
        $this->waitForResults($startedCalls);
    }

    /**
     * Order calls so that those that can be called in parallel and don't block
     * are first, and the blocking ones are last.
     *
     * This allows us to start the non-blocking calls first, then handle
     * the blocking ones and then get back to the non-blocking and wait for them.
     *
     * Potentially non-blocking calls are HTTP, CLI and PHP-CLI.
     * Blocking calls are of the PHP type.
     *
     * I tried to use the usort() function but found this manual sorting
     * to be easier to read and understand in this case.
     *
     * @param CallInterface[] $calls
     *
     * @return array Sorted calls, non-blocking first
     */
    private function orderCalls(array $calls)
    {
        $sortedCalls = [
            self::NON_BLOCKING => array(),
            self::BLOCKING => array(),
        ];

        foreach($calls as $call) {
            if ($call->isBlocking()) {
                $sortedCalls[self::BLOCKING][] = $call;
            } else {
                $sortedCalls[self::NON_BLOCKING][] = $call;

            }
        }

        return array_merge(
            $sortedCalls[self::NON_BLOCKING],
            $sortedCalls[self::BLOCKING]
        );
    }

    /**
     * Start all calls
     *
     * @param CallInterface[] $calls
     *
     * @return CallInterface[] Started calls
     */
    private function startCalls(array $calls)
    {
        foreach ($calls as $i => $call) {
            $this->processControl->checkForSignals();

            // If the dispatcher must terminate, don't start new calls,
            // and return the jobs back to the queue.
            if ($this->mustTerminate) {
                unset($calls[$i]);
                $job = $call->getJob();
                $jobId = $job->getId();
                $this->disque->nack($jobId);

                continue;
            }

            $call->call();
        }

        return $calls;
    }

    /**
     * Wait for calls to finish and handle their result as they come in
     *
     * @param CallInterface[] $calls
     */
    private function waitForResults(array $calls)
    {
        while ( ! empty($calls)) {
            foreach ($calls as $key => $call) {
                $call->checkTimeout();

                if ( ! $call->isRunning()) {
                    unset($calls[$key]);
                    $this->handleResult($call);
                }
            }

            usleep(self::LOOP_PAUSE);
        }
    }

    /**
     * Handle the result of processing a job
     *
     * @param CallInterface $call
     */
    private function handleResult(CallInterface $call)
    {
        if ($call->wasSuccessful()) {
            $this->handleSuccess($call);
        } else {
            $this->handleFailure($call);
        }
    }

    /**
     * Handle a successful call
     *
     * @param CallInterface $call
     */
    private function handleSuccess(CallInterface $call)
    {
        $job = $call->getJob();
        $jobId = $job->getId();

        $context[JobLogger::JOB_INDEX] = $job;
        $this->logger->info(
            MessageFormatter::jobProcessed($job->getId(), $job->getQueue(), $job->getOriginalId()),
            $context
        );

        try {
            $this->disque->ackJob($jobId);
        } catch (Exception $e) {
            $this->logger->error(
                MessageFormatter::failedToAck($jobId, $job->getQueue(), $e->getMessage(), $job->getOriginalId())
            );
        }
    }

    /**
     * Handle a failed call
     *
     * @param CallInterface $call
     */
    private function handleFailure(CallInterface $call)
    {
        $job = $call->getJob();
        $queue = $job->getQueue();
        $failureStrategy = $this->failureStrategies->getFailureStrategy($queue);

        $failureStrategy->handleFailure($call);
    }
}
