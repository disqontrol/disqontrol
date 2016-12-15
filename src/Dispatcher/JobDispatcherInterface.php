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
use Disqontrol\Job\JobInterface;

/**
 * Dispatcher calls the right workers to process jobs
 *
 * Dispatcher decides to what worker a job goes, how the worker should be called
 * and what constitutes a success or a failure reply.
 *
 * The decision what worker should be called for a particular job is delegated
 * to the Router. Router returns its decision in form of a Call.
 * @see Disqontrol\Dispatcher\CallInterface
 *
 * Call is an object that knows how exactly to call a worker and how
 * to tell a success from a failure.
 * 
 * @author Martin Schlemmer
 */
interface JobDispatcherInterface
{
    /**
     * Find the proper worker and call it to process the job
     *
     * @param JobInterface[] $jobs Array of jobs to dispatch
     *
     * @return ?
     * TODO: For synchronous jobs, the method can return a boolean.
     *       For asynchronous jobs, other methods must be used - callbacks/promises?
     */
    public function dispatch(array $jobs);

    /**
     * Signal the dispatcher to shut down and clean up
     */
    public function terminate();
}
