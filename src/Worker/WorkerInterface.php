<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Worker;

use Disqontrol\Job\JobInterface;

/**
 * An interface for a PHP worker called directly by Disqontrol
 *
 * If you want Disqontrol to call your PHP workers directly, whether inline
 * (directly in the Consumer process) or via CLI (wrapped in an independent
 * process for one job), implement this interface in your workers.
 *
 * You can also wrap your workers in a console command by yourself. Then of course
 * you don't have to follow this interface, just listen for the right
 * command-line or HTTP arguments. Workers called via a console command or
 * an HTTP request can be written in any language.
 *
 * If you want a PHP worker to be called directly by Disqontrol:
 *
 * - Write a PHP worker
 * - Write a WorkerFactory for the worker
 * - Extract the setup code needed to set up the environment for the workers
 * - During Disqontrol's bootstrap, create a new WorkerFactoryCollection
 * - Register the worker setup code with the collection
 * - Add all worker factories
 * - Inject the WorkerFactoryCollection into Disqontrol
 *
 * The worker setup code will be called just once and only if your PHP worker
 * is actually needed to perform a job.
 *
 * Disqontrol will then ask the proper WorkerFactory for the worker.
 *
 * @see WorkerFactoryCollectionInterface
 * @see WorkerFactoryInterface
 *
 * @author Martin Schlemmer
 */
interface WorkerInterface
{
    /**
     * Process the given job and return the result
     *
     * @param JobInterface $job The job to process
     *
     * @return bool The result of the job
     */
    public function process(JobInterface $job);
}
