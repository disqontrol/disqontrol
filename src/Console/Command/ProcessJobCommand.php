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
 * Process a job immediately via a synchronous producer
 *
 * This is a synchronous equivalent to the addjob command.
 * Instead of adding the job, the command processes it directly and returns
 * the result.
 *
 * Usage:
 * disqontrol processjob queue 'json-body'
 *
 * @author Martin Schlemmer
 */
class ProcessJobCommand extends Command
{
    const NAME = 'processjob';

    /**
     * A synchronous producer that processes new jobs instead of adding them
     * to the queue
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
        $queue = $input->getArgument(AddJobCommand::ARGUMENT_QUEUE);
        $body = $input->getArgument(AddJobCommand::ARGUMENT_JOB_BODY);

        $body = $this->serializer->deserialize($body);
        
        $job = new Job($body, $queue);
        $jobProcessed = $this->producer->add($job);
        
        if ( ! $jobProcessed) {
            exit(1);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Process a new job immediately instead of adding it to the queue.
A synchronous equivalent to the "' . AddJobCommand::NAME . '" command.')
            ->addArgument(
                AddJobCommand::ARGUMENT_QUEUE,
                InputArgument::REQUIRED,
                'The name of the queue the job belongs to'
            )
            ->addArgument(
                AddJobCommand::ARGUMENT_JOB_BODY,
                InputArgument::REQUIRED,
                'A JSON-serialized job body'
            );
    }
}
