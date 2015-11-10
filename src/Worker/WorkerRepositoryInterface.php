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

/**
 * A collection of user-written PHP workers Disqontrol can use
 *
 * Workers can be called by Consumers via CLI, HTTP, as an inline PHP code
 * (directly in the Consumer process) or as a CLI PHP (which is basically just
 * a wrapper of the inline PHP worker in its own independent process).
 *
 * Use this class to configure the inline PHP workers and CLI PHP workers and
 * inject them into Disqontrol.
 *
 * The WorkerRepository fulfills two main functions:
 *
 * 1. Because user-defined workers need their own configuration and dependencies,
 * this class allows you to define a setup process for your workers.
 * This can be anything your workers need - loading your configuration, connecting
 * to the database, creating a service/DI container etc.
 * 
 * This worker setup is run only once and only if Disqontrol actually
 * needs to call your worker. It will be called in three contexts:
 * 
 * - In a Consumer for inline PHP workers
 * - In a CLI command for PHP-CLI workers
 * - In your application if the synchronous mode is on, for both PHP and PHP-CLI
 *   workers
 * 
 * (Synchronous mode is a debug mode, wherein the Producer skips the queue
 * and calls the worker directly, thus processing the job synchronously.)
 * 
 * Beware especially of the third context and make the setup idempotent,
 * that is check that you don't do the work twice. Your app has already started
 * and probably has everything your worker needs. Use require_once and check
 * a global variable or a constant that would be set by your app bootstrap.
 *
 * To separate the worker setup from Disqontrol bootstrap is optional, but
 * recommended. If you create your worker's dependencies directly
 * in the Disqontrol bootstrap, the long-running processes may be too large,
 * heavy, and may contain memory leaks.
 *
 * Only some Consumers will need to access your PHP workers.
 * 
 * 2. The second function of this class is to store the workers themselves,
 * or more precisely, store builders that can return these workers on demand.
 * 
 * We have decided to call these worker-creating objects "builders" to use
 * as flexible a name as possible, but they can actually be simple factories,
 * or they can even just store pre-instantiated worker instances.
 * 
 * The worker setup described in point 1 is a callable that can return
 * anything. The worker builders will receive the result of the setup code.
 * You can thus for example inject a service container into the builders.
 * 
 * If you choose this "inject setup result into the worker builder" method,
 * don't forget to make it idempotent too, independent of whether your app
 * has already started or whether the code is called in a Disqontrol process.
 * 
 * @author Martin Schlemmer
 */
interface WorkerRepositoryInterface
{
    /**
     * Register a worker builder for the given worker name
     * 
     * Worker builder is an object that returns a new worker with all its
     * dependencies.
     *
     * Use this method to register your worker builders.
     * 
     * @param                        $workerName    The worker name as used
     *                                              in the configuration
     * @param WorkerBuilderInterface $workerBuilder The worker builder
     */
    public function registerWorkerBuilder($workerName, WorkerBuilderInterface $workerBuilder);
    
    /**
     * Define a code that should run once to set up workers' environment
     *
     * This code can run in different contexts (see the class docblock for
     * details) and the consequence of this is that the setup code should
     * be idempotent - meaning it should not matter whether it has already run
     * and whether it is called inside your already started application.
     * 
     * Whatever the setup code returns, will be injected into the worker
     * builders before they are asked for a worker.
     *
     * Use this method to register the setup code required by your workers.
     *
     * @param callable $workerSetup Code to set up workers
     */
    public function defineWorkerSetup(callable $workerSetup);
    
    /**
     * Get the worker defined under the given name
     *
     * This method is called by Disqontrol to fetch the PHP worker responsible
     * for processing a job.
     * @internal
     *
     * @param string $workerName
     *
     * @return WorkerInterface $worker
     */
    public function getWorker($workerName);
}
