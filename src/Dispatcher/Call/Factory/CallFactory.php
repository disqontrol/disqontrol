<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
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
     *                       indexed by the type ('cli', 'http', 'php'...)
     */
    private $factories = [];

    /**
     * @param CallFactoryInterface $cliCallFactory
     */
    public function __construct(
        CallFactoryInterface $cliCallFactory
    ) {
        $this->factories[WorkerType::CLI] = $cliCallFactory;
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
