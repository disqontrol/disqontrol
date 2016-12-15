<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer\Process;

use Disqontrol\Console\Command\ConsumerCommand;
use Psr\Log\LoggerInterface;
use Disqontrol\Logger\MessageFormatter as Msg;
use Disqontrol\DisqontrolApplication as App;

/**
 * Spawn a new consumer process
 *
 * @author Martin Schlemmer
 */
class ConsumerProcessSpawner
{
    /**
     * @var string A sprintf pattern for the consumer command
     *
     * disqontrol consumer --batch=2 [--burst] foo-queue --bootstrap=foo.php
     */
    const CONSUMER_CMD = ConsumerCommand::COMMAND_NAME .
        ' --' . ConsumerCommand::OPTION_BATCH . '=%1$d %2$s %3$s';

    const BOOTSTRAP_ARGUMENT = ' --' . App::BOOTSTRAP_ARGUMENT . '=%4$s';
    
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string Path to the bootstrap file
     */
    private $bootstrapFilePath;
    
    /**
     * @param string          $bootstrapFilePath
     * @param LoggerInterface $logger
     */
    public function __construct(
        $bootstrapFilePath,
        LoggerInterface $logger
    ) {
        $this->bootstrapFilePath = $bootstrapFilePath;
        $this->logger = $logger;
    }
    
    /**
     * @param string[] $queues    Queues the consumer should fetch jobs from
     * @param int      $jobBatch  How many jobs to reserve at once
     * @param bool     $burstMode Is the consumer in a burst mode?
     *
     * @return ConsumerProcess
     */
    public function spawn(array $queues, $jobBatch, $burstMode = false)
    {
        $cwd = null;
        $env = null;
        $input = null;
        // The consumer process should not time out
        $timeout = null;

        $burstArgument = '';
        if ($burstMode) {
            $burstArgument = '--' . ConsumerCommand::OPTION_BURST;
        }

        $queues = implode(' ', $queues);

        $cmdPattern = App::getExecutable() . self::CONSUMER_CMD;
        if( ! empty($this->bootstrapFilePath)) {
            $cmdPattern .= self::BOOTSTRAP_ARGUMENT;
        }

        // @todo Run the consumer in debug mode if supervisor is in debug mode?
        $cmd = sprintf(
            $cmdPattern,
            $jobBatch,
            $burstArgument,
            $queues,
            $this->bootstrapFilePath
        );
        
        $this->logger->debug(Msg::startingConsumerProcess($cmd));

        $process = new ConsumerProcess($cmd, $cwd, $env, $input, $timeout);
        $process->setBurstMode($burstMode);
        $process->start();

        return $process;
    }
}
