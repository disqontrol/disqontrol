<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Console\Command;

use Disqontrol\Configuration\Configuration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show list of configured queues
 *
 * @author Martin Patera <mzstic@gmail.com>
 */
class ListQueuesCommand extends Command
{
    /**
     * List of queues.
     *
     * @var array
     */
    protected $queues;

    /**
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        parent::__construct();
        $this->queues = $config->getQueuesConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('list-queues')
            ->setDescription('Show list of configured queues.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->queues as $queueName => $queue) {
            $output->writeln($queueName);
        }
    }
}
