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

use Disqontrol\Job\Serializer\SerializerInterface;
use Disqontrol\Producer\ProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Disqontrol\Job\Job;

/**
 * Add a job to the queue via a console command
 *
 * Usage:
 * disqontrol addjob queue 'json-body' [--delay|-d=DELAY]
 *
 * @author Martin Schlemmer
 */
class AddJobCommand extends Command
{
    const NAME = 'addjob';
    const ARGUMENT_QUEUE = 'queue';
    const ARGUMENT_JOB_BODY = 'job-body';
    const OPTION_DELAY = 'delay';
    const OPTION_DELAY_SHORT = 'd';

    /**
     * A producer that adds new jobs to the queue
     *
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ProducerInterface   $producer
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ProducerInterface $producer,
        SerializerInterface $serializer
    ) {
        $this->producer = $producer;
        $this->serializer = $serializer;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getArgument(self::ARGUMENT_QUEUE);
        $body = $input->getArgument(self::ARGUMENT_JOB_BODY);
        $delay = (int)$input->getOption(self::OPTION_DELAY);

        $body = $this->serializer->deserialize($body);

        $job = new Job($body, $queue);
        $jobAdded = $this->producer->add($job, $delay);

        if ( ! $jobAdded) {
            exit(1);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $defaultDelay = 0;

        $this
            ->setName(self::NAME)
            ->setDescription('Add a new job to the queue')
            ->addArgument(
                self::ARGUMENT_QUEUE,
                InputArgument::REQUIRED,
                'The name of the queue the job will be sent to'
            )
            ->addArgument(
                self::ARGUMENT_JOB_BODY,
                InputArgument::REQUIRED,
                'A JSON-serialized job body'
            )
            ->addOption(
                self::OPTION_DELAY,
                self::OPTION_DELAY_SHORT,
                InputOption::VALUE_OPTIONAL,
                'The job delay in seconds',
                $defaultDelay
            );
    }
}
