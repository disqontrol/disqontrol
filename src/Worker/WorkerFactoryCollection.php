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

use Disqontrol\Exception\ConfigurationException;
use Disqontrol\Logger\MessageFormatter as msg;

/**
 * Register PHP workers from your application to Disqontrol in this class
 *
 * For more explanation
 * @see WorkerInterface
 * @see WorkerFactoryCollectionInterface
 *
 * @author Martin Schlemmer
 */
class WorkerFactoryCollection implements WorkerFactoryCollectionInterface
{
    /**
     * @var WorkerFactoryInterface[] A collection of worker factories
     */
    private $workerFactories = array();

    /**
     * @var callable A code that sets up the worker environment
     */
    private $workerEnvironmentSetup;

    /**
     * @var mixed The worker environment; the result of the setup code
     */
    private $workerEnvironment = null;

    /**
     * @var bool Has the worker environment been set up yet?
     */
    private $hasEnvironmentBeenSetUp = false;

    /**
     * {@inheritdoc}
     */
    public function addWorkerFactory($workerName, WorkerFactoryInterface $workerFactory)
    {
        $this->workerFactories[$workerName] = $workerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function registerWorkerEnvironmentSetup(callable $workerSetup)
    {
        $this->workerEnvironmentSetup = $workerSetup;
        $this->hasEnvironmentBeenSetUp = false;
        $this->workerEnvironment = null;
    }

    /**
     * {@inheritdoc}
     */
    public function workerExists($workerName)
    {
        return isset($this->workerFactories[$workerName]);
    }

    /**
     * {@inheritdoc}
     */
    public function getWorker($workerName)
    {
        $this->setUpWorkerEnvironment();

        if ( ! $this->workerExists($workerName)) {
            throw new ConfigurationException(msg::phpJobWorkerNotFound($workerName));
        }

        $factory = $this->workerFactories[$workerName];
        $worker = $factory->create($this->workerEnvironment);

        return $worker;
    }

    /**
     * Set up the workers' environment once
     */
    private function setUpWorkerEnvironment()
    {
        if ($this->hasEnvironmentBeenSetUp
            || ! is_callable($this->workerEnvironmentSetup)) {
            return;
        }

        $this->workerEnvironment = call_user_func($this->workerEnvironmentSetup);
        $this->hasEnvironmentBeenSetUp = true;
    }
}
