<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Disqontrol\Console\Command;

use Disqontrol\Configuration\Configuration;
use Disqontrol\Configuration\ConfigDefinition as Config;
use Disqontrol\Consumer\ConsumerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command for starting the consumer
 *
 * Run:
 * disqontrol consumer [options] [--] <queue> (<queue>)...
 *
 * Options:
 * -b, --batch[=BATCH]   How many jobs the consumer should process at once
 * --burst               Turn on the burst mode, ie. the consumer will exit
 *                       after there are no more jobs in the queue
 *
 * Full example:
 * disqontrol consumer --batch=4 --burst registration-email profile-update pic-resize
 *
 * @author Martin Schlemmer
 */
class ConsumerCommand extends Command
{
    /**
     * Names of the command options and arguments
     */
    const COMMAND_NAME = 'consumer';
    const OPTION_BATCH = 'batch';
    const OPTION_BATCH_SHORT = 'b';
    const OPTION_BURST = 'burst';
    const ARGUMENT_QUEUES = 'queues';

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var ConsumerInterface
     */
    private $consumer;

    /**
     * @param Configuration     $config
     * @param ConsumerInterface $consumer
     */
    public function __construct(
        Configuration $config,
        ConsumerInterface $consumer
    ) {
        $this->config = $config;
        $this->consumer = $consumer;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $burstMode = (bool) $input->getOption(self::OPTION_BURST);
        $jobBatch = (int) $input->getOption(self::OPTION_BATCH);
        $queues = $input->getArgument(self::ARGUMENT_QUEUES);

        $this->consumer->listen($queues, $jobBatch, $burstMode);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Start a job consumer [Internal command]');

        $defaultBatch = $this->config->getConsumerDefaults()[Config::JOB_BATCH];
        $this->addOption(
            self::OPTION_BATCH,
            self::OPTION_BATCH_SHORT,
            InputOption::VALUE_OPTIONAL,
            'How many jobs the consumer should process at once',
            $defaultBatch
        );

        $shortBurstName = null;
        $this->addOption(
            self::OPTION_BURST,
            $shortBurstName,
            InputOption::VALUE_NONE,
            'Turn on the burst mode, ie. the consumer will exit after there are no more jobs in the queue'
        );

        $this->addArgument(
            self::ARGUMENT_QUEUES,
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'Space-separated names of the queues the consumer should watch'
        );
    }
}
