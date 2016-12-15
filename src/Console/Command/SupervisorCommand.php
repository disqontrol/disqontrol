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

use Symfony\Component\Console\Command\Command;
use Disqontrol\Supervisor\Supervisor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command for starting the supervisor (the top-level process)
 *
 * @author Martin Schlemmer
 */
class SupervisorCommand extends Command
{
    /**
     * @var Supervisor
     */
    private $supervisor;

    /**
     * @param Supervisor $supervisor
     */
    public function __construct(Supervisor $supervisor)
    {
        $this->supervisor = $supervisor;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->supervisor->run();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('supervisor')
            ->setDescription('Start the Disqontrol supervisor. Keep this command running all the time.');
    }
}
