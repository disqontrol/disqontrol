<?php
/*
 * This file is part of the Disqontrol package.
 *
 * (c) Webtrh s.r.o. <info@webtrh.cz>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Disqontrol\Configuration;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Disqontrol\Disqontrol;

/**
 * Add console commands to the container parameter "disqontrol.commands"
 *
 * @author Martin Patera <mzstic@gmail.com>
 * @author Martin Schlemmer
 */
class ConsoleCommandsCompilerPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $commandIds = $container->findTaggedServiceIds('disqontrol.command');
        $container->setParameter(
            Disqontrol::CONTAINER_COMMANDS_KEY,
            array_keys($commandIds)
        );
    }
}
