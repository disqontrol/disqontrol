<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Dispatcher\Call\Factory;

use Disqontrol\Exception\JobRouterException;
use Disqontrol\Job\JobInterface;
use Disqontrol\Router\WorkerDirectionsInterface;
use Disqontrol\Worker\WorkerType;

/**
 * {@inheritdoc}
 *
 * @author Martin Schlemmer
 */
class CallFactory implements CallFactoryInterface
{
    /**
     * @var CallFactoryInterface[] Factories for different types of calls
     *                       indexed by the type ('cli', 'http'...)
     */
    private $factories = [];

    /**
     * @param CallFactoryInterface $cliCallFactory
     * @param CallFactoryInterface $phpCallFactory
     * @param CallFactoryInterface $isolatedPhpCallFactory
     */
    public function __construct(
        CallFactoryInterface $cliCallFactory,
        CallFactoryInterface $phpCallFactory,
        CallFactoryInterface $isolatedPhpCallFactory
    ) {
        $this->factories[WorkerType::CLI] = $cliCallFactory;
        $this->factories[WorkerType::INLINE_PHP_WORKER] = $phpCallFactory;
        $this->factories[WorkerType::ISOLATED_PHP_WORKER] = $isolatedPhpCallFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createCall(
        WorkerDirectionsInterface $directions,
        JobInterface $job
    ) {
        $workerType = $directions->getType()->getConstValue();

        if ( ! isset($this->factories[$workerType])) {
            throw new JobRouterException(
                sprintf(
                    'Unsupported worker type "%s" when routing job %s from queue %s',
                    $workerType,
                    $job->getId(),
                    $job->getQueue()
                )
            );
        }

        return $this->factories[$workerType]->createCall($directions, $job);
    }
}
