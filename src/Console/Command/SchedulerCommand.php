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

use Disqontrol\Scheduler\Scheduler;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Disqontrol\Exception\FilesystemException;
use Disqontrol\Logger\MessageFormatter as Msg;

/**
 * A command for running the job scheduler
 *
 * Scheduler looks at the crontab under the given path and sends those jobs
 * to their queue, that should be run right now.
 *
 * This command should be run every minute.
 *
 * Run:
 * disqontrol scheduler --crontab=/path/to/crontab
 *
 * @author Martin Schlemmer
 */
class SchedulerCommand extends Command
{
    const OPTION_CRONTAB = 'crontab';
    const OPTION_CRONTAB_SHORT = 'c';

    const HELP = <<<'HEREDOC'
Run a job scheduler that checks which jobs from a Disqontrol crontab should run.
Run the scheduler every minute by adding this entry to your system crontab:

<info>* * * * * /path/to/disqontrol scheduler --crontab=/path/to/crontab >/dev/null 2>&1</info>

A Disqontrol crontab row has the following syntax:

<info>* * * * * queue job-body</info>

Where
- <comment>the asterisks</comment> follow the common cron syntax (minute, hour, day, month, weekday),
- <comment>"queue"</comment> is the name of the job queue and
- <comment>"job-body"</comment> is the body of the scheduled job. The body can contain white spaces.

An example crontab with regular jobs may look like this:

<info>15 5 * * * daily-cleanup 1
34 2 * * 5 weekly-pruning all
*/5 * * * * five-minute-checkup 1</info>

The first job will run every day at 05:15 AM, the second job will run every
Friday at 02:34 AM and the third job will run every 5 minutes.
HEREDOC;
    
    /**
     * @var Scheduler
     */
    private $scheduler;

    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param Scheduler       $scheduler
     * @param LoggerInterface $logger
     */
    public function __construct(Scheduler $scheduler, LoggerInterface $logger)
    {
        $this->scheduler = $scheduler;
        $this->logger = $logger;
        
        parent::__construct();
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $crontabPath = $input->getOption(self::OPTION_CRONTAB);

        if (empty($crontabPath)) {
            $msg = Msg::missingCrontabPath();
            $this->logger->critical($msg);
            throw new Exception($msg);
        }

        if ( ! file_exists($crontabPath)) {
            $msg = Msg::fileNotFound($crontabPath);
            $this->logger->critical($msg);
            throw new FilesystemException($msg);
        }

        $crontab = file_get_contents($crontabPath);
        $this->scheduler->scheduleJobs($crontab);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('scheduler')
            ->setDescription('Run the job scheduler. Run this command every minute.')
            ->setHelp(self::HELP)
            ->addOption(
                self::OPTION_CRONTAB,
                self::OPTION_CRONTAB_SHORT,
                InputOption::VALUE_REQUIRED,
                'A path to the crontab file'
            );
    }
    
}
