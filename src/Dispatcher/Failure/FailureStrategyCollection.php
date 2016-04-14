<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Failure;

use Disqontrol\Configuration\Configuration;
use Disqontrol\Configuration\ConfigDefinition as Config;
use LogicException;

/**
 * A collection of failure strategies with a logic for fallback strategies
 *
 * This is a collection of failure strategies, behaviors used when a job fails.
 * It can return the proper strategy for a given queue. It can also fall back
 * to a strategy in case no matching strategy is found.
 *
 * The strategy is chosen by the following steps:
 * 1. Return the strategy stored under the key defined in the configuration
 * 2. Return the strategy stored under the key 'retry'
 * 3. Return the first registered strategy
 *
 * Step 1 is clear, users can set their preferred strategy in the configuration.
 * If the user-set strategy is not found, try to fall back to the default
 * 'retry' strategy.
 * If the 'retry' strategy is not found, fall back to the first registered
 * strategy. This step is meant for a FailureStrategyCollection used
 * in synchronous mode, because it should contain only one strategy -
 * LogAndThrowAway. No other behaviors make sense in synchronous mode.
 *
 * @author Martin Schlemmer
 */
class FailureStrategyCollection
{
    /**
     * A fallback failure strategy if a strategy was not found
     *
     * The service container must contain a failure strategy under this name
     */
    const FALLBACK_FAILURE_STRATEGY = 'retry';

    /**
     * @var Configuration
     */
    private $config;

    /**
     * Failure strategies to choose from
     *
     * @var FailureStrategyInterface[]
     */
    private $failureStrategies = array();

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Register a failure strategy the queues can use
     *
     * @param string                   $id       The strategy ID
     * @param FailureStrategyInterface $strategy
     */
    public function addFailureStrategy($id, FailureStrategyInterface $strategy)
    {
        $this->failureStrategies[$id] = $strategy;
    }

    /**
     * Get the failure strategy for the given queue
     *
     * @param string $queue
     *
     * @return FailureStrategyInterface
     *
     * @throws LogicException
     */
    public function getFailureStrategy($queue)
    {
        $strategyName = $this->config->getFailureStrategyName($queue);

        if ( ! empty($this->failureStrategies[$strategyName])) {
            return $this->failureStrategies[$strategyName];
        }

        if ( ! empty($this->failureStrategies[Config::FAILURE_STRATEGY_DEFAULT])) {
            return $this->failureStrategies[Config::FAILURE_STRATEGY_DEFAULT];
        }

        if (0 < count($this->failureStrategies)) {
            return current($this->failureStrategies);
        }

        // If we get here, we have a problem - no failure strategy is defined.
        // This is an unrecoverable error, something is wrong in the configuration.
        // We need the process to crash loudly and quickly.
        throw new LogicException('No failure strategy found');
    }
}
