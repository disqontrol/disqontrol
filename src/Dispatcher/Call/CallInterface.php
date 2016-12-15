<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Call;

use Disqontrol\Job\JobInterface;
use Disqontrol\Worker\WorkerType;

/**
 * Call a job worker and interpret its results
 *
 * This object wraps the call to a job worker. It knows how to call it, where
 * to reach it and what arguments to send. It also understands the return codes
 * and knows if the worker was successful in performing the job or not.
 *
 * @author Martin Schlemmer
 */
interface CallInterface
{
    /**
     * Is the call blocking?
     *
     * @return bool
     */
    public function isBlocking();

    /**
     * Call the worker with the job
     *
     * This method should be idempotent, multiple method calls should not result
     * in multiple requests.
     */
    public function call();

    /**
     * Is the call still running?
     *
     * True - running
     * False - finished
     *
     * @return bool
     */
    public function isRunning();

    /**
     * Check whether the call has timed out and stop it if it has
     */
    public function checkTimeout();

    /**
     * Get the result of this call
     *
     * If the call hasn't finished yet, this method will block until the call
     * finishes.
     *
     * @return bool Was the job processed successfully?
     */
    public function wasSuccessful();

    /**
     * Get a human readable error message if the call failed, or empty string
     *
     * The purpose of this method is to provide human readable errors.
     * Methods in Call classes shouldn't throw exceptions. They will exist
     * in long-running processes and exceptions are dangerous. Instead, they
     * should provide useful messages for logging.
     *
     * The output of this method will be logged as an error.
     *
     * @return string
     */
    public function getErrorMessage();

    /**
     * Get the processed job
     *
     * @return JobInterface
     */
    public function getJob();

    /**
     * Get the type of the call
     *
     * @return WorkerType
     */
    public function getType();
}
