<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher;

use Disqontrol\Dispatcher\Failure\FailureStrategyInterface;
use Disqontrol\Router\JobRouterInterface;

/**
 * A job dispatcher that dispatches the job immediately and doesn't touch Disque
 */
class SynchronousJobDispatcher implements JobDispatcherInterface
{
    /**
     * JobRouter decides what worker is responsible for each job and how it
     * should be called.
     *
     * @var JobRouterInterface
     */
    private $jobRouter;
    
    /**
     * A strategy to handle failed jobs
     *
     * @var FailureStrategyInterface
     */
    private $failureStrategy;

    /**
     * @param JobRouterInterface       $jobRouter
     * @param FailureStrategyInterface $failureStrategy
     */
    public function __construct(
        JobRouterInterface $jobRouter,
        FailureStrategyInterface $failureStrategy
    ) {
        $this->jobRouter = $jobRouter;
        $this->failureStrategy = $failureStrategy;
    }

    /**
     * This is a simplified version of the normal JobDispatcher
     *
     * We only ever get one job and we don't have to handle its results.
     *
     * {@inheritdoc}
     */
    public function dispatch(array $jobs)
    {
        $job = current($jobs);
        $call = $this->jobRouter->getCall($job);
        $call->call();

        while (true) {
            $call->checkTimeout();

            if ( ! $call->isRunning()) {
                break;
            }

            usleep(JobDispatcher::LOOP_PAUSE);
        }

        if ( ! $call->wasSuccessful()) {
            $this->failureStrategy->handleFailure($call);
        }

        return $call->wasSuccessful();
    }

    /**
     * Unused in a synchronous job dispatcher
     *
     * {@inheritdoc}
     */
    public function terminate()
    {
        return;
    }
}
