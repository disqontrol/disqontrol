<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class DisqontrolExtension
 *
 * @author Martin Patera <mzstic@gmail.com>
 */
class DisqontrolCompilerPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $commandIds = $container->findTaggedServiceIds('disqontrol.command');
        $container->setParameter('disqontrol.commands', array_keys($commandIds));
    }
}
