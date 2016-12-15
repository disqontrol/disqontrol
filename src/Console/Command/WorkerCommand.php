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

use Disqontrol\Dispatcher\Call\Cli\CliCall;
use Disqontrol\Worker\PhpWorkerExecutor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Disqontrol\Logger\MessageFormatter as Msg;

/**
 * A command for running a PHP worker
 *
 * This is an internal command that Disqontrol uses to isolate PHP workers
 * in a separate process.
 *
 * Example:
 * disqontrol worker WorkerFoo --queue=queue-bar --body=1 --metadata=baz --bootstrap=foo.php
 *
 * @author Martin Schlemmer
 */
class WorkerCommand extends Command
{
    const NAME = 'worker';
    const ARGUMENT_WORKER_NAME = 'worker_name';
    const OPTION_QUEUE = 'queue';

    /**
     * @var PhpWorkerExecutor
     */
    private $phpWorkerExecutor;

    /**
     * @param PhpWorkerExecutor $phpWorkerExecutor
     */
    public function __construct(
        PhpWorkerExecutor $phpWorkerExecutor
    ) {
        $this->phpWorkerExecutor = $phpWorkerExecutor;

        parent::__construct();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $workerName = $input->getArgument(self::ARGUMENT_WORKER_NAME);
        $jobQueue = $input->getOption(self::OPTION_QUEUE);
        $jobBody = $input->getOption(CliCall::ARGUMENT_BODY);
        $jobMetadata = $input->getOption(CliCall::ARGUMENT_METADATA);

        if (empty($jobQueue) || empty($jobBody) || empty($jobMetadata)) {
            throw new InvalidArgumentException(
                Msg::workerCommandMissingParameters(self::NAME)
            );
        }

        $result = $this->phpWorkerExecutor->process(
            $workerName,
            $jobQueue,
            $jobBody,
            $jobMetadata
        );

        if ($result === false) {
            // Die with an error code so the caller knows that the job wasn't
            // processed correctly.
            die(1);
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $noShortVersion = null;

        $this
            ->setName(self::NAME)
            ->setDescription('Call a PHP worker in a separate process [Internal command]')
            ->addArgument(
                self::ARGUMENT_WORKER_NAME,
                InputArgument::REQUIRED,
                'The PHP worker name'
            )
            ->addOption(
                self::OPTION_QUEUE,
                $noShortVersion,
                InputOption::VALUE_REQUIRED,
                'The job queue'
            )
            ->addOption(
                CliCall::ARGUMENT_BODY,
                $noShortVersion,
                InputOption::VALUE_REQUIRED,
                'The job body'
            )
            ->addOption(
                CliCall::ARGUMENT_METADATA,
                $noShortVersion,
                InputOption::VALUE_REQUIRED,
                'The job metadata'
            );
    }
    
}
