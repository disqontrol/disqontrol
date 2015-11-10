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
 * A class that can return a PHP worker
 *
 * A worker builder can create and return a PHP worker. Builders should be
 * registered in the WorkerRepository during Disqontrol's bootstrap.
 *
 * The WorkerRepository will also run a setup code, setting up
 * the environment for the workers, before calling any worker builder.
 *
 * The WorkerRepository will then inject anything the setup has returned
 * into the worker builder before asking for a worker. You can use this process
 * to inject for example a service container into the builder.
 *
 * @author Martin Schlemmer
 */
interface WorkerBuilderInterface
{
    /**
     * Inject the worker setup result before calling any other method
     *
     * This method is used by Disqontrol to inject anything the user-defined
     * worker setup code has returned. This allows you to explicitly
     * inject at least a service container or a similar service locator
     * without having to resort to global variables.
     *
     * @internal
     * @see WorkerRepositoryInterface::defineWorkerSetup()
     *
     * @param mixed $setupResult The result of the worker setup code
     */
    public function injectSetupResult($setupResult);

    /**
     * Return a worker instance
     *
     * Whether this always returns a new instance or the same object
     * is up to you.
     *
     * @return WorkerInterface
     */
    public function getWorker();
}
