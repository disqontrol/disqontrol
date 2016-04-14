<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Consumer\Process;

use Psr\Log\LoggerInterface;
use Disqontrol\Logger\MessageFormatter as Msg;

/**
 * Spawn a new consumer process
 *
 * @author Martin Schlemmer
 */
class ConsumerProcessSpawner
{
    /**
     * @var string A sprintf pattern for the consumer command
     */
    const CONSUMER_CMD = './disqontrol consumer --batch=%1$d %2$s %3$s';
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
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
            $burstArgument = '--burst';
        }

        $queues = implode(' ', $queues);

        // @todo Run the consumer in debug mode if supervisor is in debug mode?
        $cmd = sprintf(self::CONSUMER_CMD, $jobBatch, $burstArgument, $queues);
        
        $this->logger->debug(Msg::startingConsumerProcess($cmd));

        $process = new ConsumerProcess($cmd, $cwd, $env, $input, $timeout);
        $process->setBurstMode($burstMode);
        $process->start();

        return $process;
    }
}
