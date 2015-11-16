<?php
namespace Disqontrol;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;


/**
 * Class DisqontrolExtension
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