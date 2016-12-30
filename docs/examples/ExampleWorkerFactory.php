<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Mediaplus.cz s.r.o. <info@mediaplus.cz>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

use Disqontrol\Worker\WorkerFactoryInterface;

/**
 * This is an example worker factory
 *
 * @see examples/disqontrol_bootstrap.php
 */
class ExampleWorkerFactory implements WorkerFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($container, $workerName)
    {
        // return $container->get($workerName);
        return new ExampleWorker();
    }
}
