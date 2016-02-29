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

use Disqontrol\Configuration\Configuration;
use Disqontrol\Dispatcher\Call\Cli\CliCall;
use Disqontrol\Dispatcher\Call\Cli\ProcessFactory;
use Disqontrol\Job\Marshaller\MarshallerInterface;
use Disqontrol\Router\WorkerDirectionsInterface;
use Disqontrol\Job\JobInterface;

/**
 * A factory for creating CLI calls
 *
 * @author Martin Schlemmer
 */
class CliCallFactory implements CallFactoryInterface
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var MarshallerInterface
     */
    private $marshaller;

    /**
     * @var ProcessFactory
     */
    private $processFactory;

    /**
     * @param Configuration       $config
     * @param MarshallerInterface $marshaller
     * @param ProcessFactory      $processFactory
     */
    public function __construct(
        Configuration $config,
        MarshallerInterface $marshaller,
        ProcessFactory $processFactory
    ) {
        $this->config = $config;
        $this->marshaller = $marshaller;
        $this->processFactory = $processFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createCall(
        WorkerDirectionsInterface $directions,
        JobInterface $job
    ) {
        $queue = $job->getQueue();
        $timeout = $this->config->getJobProcessTimeout($queue);

        return new CliCall(
            $directions,
            $job,
            $this->marshaller,
            $timeout,
            $this->processFactory
        );
    }
}
