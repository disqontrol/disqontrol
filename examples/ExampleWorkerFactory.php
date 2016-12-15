<?php
/*
* This file is part of the Disqontrol package.
*
* (c) Webtrh s.r.o. <info@webtrh.cz>
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
    public function create($container)
    {
        // return $container->get('foo_worker');
        return new ExampleWorker();
    }
}
