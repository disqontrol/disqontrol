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

use Disqontrol\Console\Command\WorkerCommand;
use Disqontrol\Dispatcher\Call\Cli\CliCall;
use Disqontrol\Job\Serializer\SerializerInterface;
use Disqontrol\Router\WorkerDirections;
use Disqontrol\Router\WorkerDirectionsInterface;
use Disqontrol\Job\JobInterface;
use Disqontrol\Worker\WorkerType;
use Disqontrol\DisqontrolApplication;

/**
 * Create a CLI call to the isolated PHP worker
 *
 * @author Martin Schlemmer
 */
class IsolatedPhpCallFactory implements CallFactoryInterface
{
    /**
     * Ex: ./disqontrol worker FooWorker --bootstrap=foo/bar.php
     * The arguments --queue=bar-queue --body=foo --metadata=baz
     * are added in CliCall
     */
    const COMMAND = './disqontrol ' . WorkerCommand::NAME . ' %1$s';

    const BOOTSTRAP_ARGUMENT = ' --' . DisqontrolApplication::BOOTSTRAP_ARGUMENT . '=%2$s';

    /**
     * @var CallFactoryInterface
     */
    private $cliCallFactory;

    /**
     * @var string Path to the bootstrap file
     */
    private $bootstrapFilePath;

    /**
     * @param CallFactoryInterface $cliCallFactory
     * @param string               $bootstrapFilePath
     */
    public function __construct(
        CallFactoryInterface $cliCallFactory,
        $bootstrapFilePath
    ) {
        $this->cliCallFactory = $cliCallFactory;
        $this->bootstrapFilePath = $bootstrapFilePath;
    }

    /**
     * {@inheritdoc}
     */
    public function createCall(
        WorkerDirectionsInterface $directions,
        JobInterface $job
    ) {
        $cmdPattern = self::COMMAND;

        if( ! empty($this->bootstrapFilePath)) {
            $cmdPattern .= self::BOOTSTRAP_ARGUMENT;
        }

        $workerName = $directions->getAddress();
        $command = sprintf(
            $cmdPattern,
            $workerName,
            $this->bootstrapFilePath
        );

        $newDirections = new WorkerDirections(WorkerType::CLI(), $command);
        $call = $this->cliCallFactory->createCall($newDirections, $job);

        return $call;
    }
}
