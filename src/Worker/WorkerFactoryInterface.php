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

/**
 * A class that can return a PHP worker
 *
 * A worker factory can create and return a PHP worker. Factories should be
 * registered in the WorkerFactoryCollection during Disqontrol's bootstrap.
 *
 * The WorkerFactoryCollection will also run a setup code, setting up
 * the environment for the workers, before calling any worker factory.
 *
 * The WorkerFactoryCollection will then inject anything the setup has returned
 * into the worker factory when asking for a worker. You can use this process
 * to inject for example a service container into the factory.
 *
 * @author Martin Schlemmer
 */
interface WorkerFactoryInterface
{
    /**
     * Return a worker instance
     *
     * Whether this always returns a new instance or the same object
     * is up to you.
     * 
     * This method receives the worker environment as a parameter.
     * The worker environment is anything that your environment setup code
     * returns.
     * You can use it to inject a service container or a similar service locator
     * without having to resort to global variables.
     *
     * @see WorkerFactoryCollectionInterface::registerWorkerEnvironmentSetup()
     * 
     * @param mixed  $workerEnvironment
     * @param string $workerName
     *
     * @return WorkerInterface
     */
    public function create($workerEnvironment, $workerName);
}
