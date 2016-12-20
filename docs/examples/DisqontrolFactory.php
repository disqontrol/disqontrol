<?php
use Disqontrol\Disqontrol;
use Disqontrol\Worker\WorkerFactoryCollection;

// Examples of workers and their factories
use OurApp\JobQueue\Worker\UpdateRssWorkerFactory;
use OurApp\JobQueue\Worker\UpdateRss;
use OurApp\JobQueue\Worker\ResizePicsWorkerFactory;
use OurApp\JobQueue\Worker\ResizePics;

/**
 * Create a Disqontrol instance with PHP workers and environment setup
 */
class DisqontrolFactory
{
    const CONFIG_PATH = 'disqontrol.yml';

    private $disqontrol;

    /**
     * Return a Disqontrol instance
     *
     * @param bool $debug
     *
     * @return Disqontrol
     */
    public function create($debug = false)
    {
        if (empty($this->disqontrol)) {
            $this->createDisqontrol($debug);
        }

        return $this->disqontrol;
    }

    /**
     * Create an environment for PHP workers in Disqontrol
     *
     * This method will be called only when needed, and only once.
     *
     * What you return here is up to you, but it should probably be something
     * that allows access to your objects, like a service container.
     *
     * WorkerFactories will receive the return variable.
     *
     * @return mixed
     */
    public function createEnvironment()
    {
        // Include a file that starts your environment
        // It shouldn't do anything if the environment is already set up.
        $serviceContainer = require_once __DIR__ . '/../../app_bootstrap.php';
        return $serviceContainer;
    }

    /**
     * Create a Disqontrol instance with PHP workers and environment setup code
     *
     * @param bool $debug
     */
    private function createDisqontrol($debug)
    {
        // Register factories for all your PHP workers
        $workerFactoryCollection = new WorkerFactoryCollection();

        $workerFactoryCollection->addWorkerFactory(
            UpdateRss::NAME,
            new UpdateRssWorkerFactory()
        );

        $workerFactoryCollection->addWorkerFactory(
            ResizePics::NAME,
            new ResizePicsWorkerFactory()
        );

        // Register the environment setup code
        $workerFactoryCollection->registerWorkerEnvironmentSetup(
            [$this, 'createEnvironment']
        );

        $this->disqontrol = new Disqontrol(
            self::CONFIG_PATH,
            $workerFactoryCollection,
            $debug
        );
    }
}
