<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Router;

use Disqontrol\Dispatcher\Call\Factory\CallFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Disqontrol\Configuration\Configuration;
use Disqontrol\Configuration\ConfigDefinition as Config;
use Disqontrol\Worker\WorkerType;
use InvalidArgumentException;
use Disqontrol\Logger\MessageFormatter;

/**
 * Initialize the Job Router properly, with all routes from the configuration
 *
 * @author Martin Schlemmer
 */
class JobRouterFactory
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var CallFactoryInterface
     */
    private $callFactory;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var JobRouterInterface
     */
    private $jobRouter;

    /**
     * @param Configuration        $config
     * @param CallFactoryInterface $callFactory
     * @param EventDispatcher      $eventDispatcher
     */
    public function __construct(
        Configuration $config,
        CallFactoryInterface $callFactory,
        EventDispatcher $eventDispatcher
    ) {
        $this->config = $config;
        $this->callFactory = $callFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Get the initialized JobRouter
     *
     * @return JobRouterInterface
     *
     * @throws InvalidArgumentException
     */
    public function getRouter()
    {
        if (empty($this->jobRouter)) {
            $this->jobRouter = new JobRouter(
                $this->callFactory,
                $this->eventDispatcher
            );
            // This method can throw an exception if the worker type is unknown
            // If we don't catch it and let the process exit, we can inform
            // the user sooner about the error. This is similar to Nginx not
            // starting if the configuration has a syntax error.
            $this->addConfigurationRoutes();
        }

        return $this->jobRouter;
    }

    /**
     * Add routes from the configuration to the JobRouter
     *
     * @throws InvalidArgumentException
     */
    private function addConfigurationRoutes()
    {
        $queueConfig = $this->config->getQueuesConfig();

        foreach ($queueConfig as $queueName => $queue) {
            $directions = $this->createDirections($queueName);
            $route = new SimpleRoute([$queueName], $directions);
            $this->jobRouter->addRoute($route);
        }
    }

    /**
     * Create WorkerDirections from a queue config array
     *
     * @param string $queue
     *
     * @return WorkerDirectionsInterface
     *
     * @throws InvalidArgumentException
     */
    private function createDirections($queue)
    {
        $workerTypeName = $this->config->getWorkerType($queue);
        $address = $this->config->getWorkerDirections($queue);

        // Parameters are meant for HTTP workers and are not implemented yet
        $parameters = array();

        $workerType = $this->stringToWorkerType($workerTypeName);
        $directions = new WorkerDirections($workerType, $address, $parameters);

        return $directions;
    }

    /**
     * Convert the worker type from a string to an object
     *
     * @param string $type Worker type string, eg. 'command', 'http'
     *
     * @return WorkerType
     *
     * @throws InvalidArgumentException
     */
    private function stringToWorkerType($type)
    {
        // I've made the mistake myself, so let's allow both dashes and
        // underscores in the worker type.
        $type = str_replace('-', '_', $type);
        try {
            $workerType = WorkerType::getByValue($type);
            return $workerType;
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                MessageFormatter::unknownWorkerType($type)
            );
        }
    }
}
