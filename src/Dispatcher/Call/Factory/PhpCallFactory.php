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

use Disqontrol\Dispatcher\Call\Php\NullWorker;
use Disqontrol\Dispatcher\Call\Php\PhpCall;
use Disqontrol\Router\WorkerDirectionsInterface;
use Disqontrol\Job\JobInterface;
use Disqontrol\Worker\WorkerFactoryCollectionInterface;
use Exception;

/**
 * A factory for creating PHP calls
 *
 * @author Martin Schlemmer
 */
class PhpCallFactory implements CallFactoryInterface
{
    /**
     * @var WorkerFactoryCollectionInterface
     */
    private $workerFactoryCollection;

    /**
     * @param WorkerFactoryCollectionInterface $workerFactoryCollection
     */
    public function __construct(
        WorkerFactoryCollectionInterface $workerFactoryCollection
    ) {
        $this->workerFactoryCollection = $workerFactoryCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function createCall(
        WorkerDirectionsInterface $directions,
        JobInterface $job
    ) {
        $workerName = $directions->getAddress();

        try {
            $worker = $this->workerFactoryCollection->getWorker($workerName);
        } catch (Exception $e) {
            // The job cannot be processed but we don't want to exit
            // the current process. So we return a PhpCall that always fails.
            // The error will be logged in the JobDispatcher and the job will
            // be retried later.
            $worker = new NullWorker($workerName);
        }

        $phpCall = new PhpCall($directions, $worker, $job);

        return $phpCall;
    }
}
