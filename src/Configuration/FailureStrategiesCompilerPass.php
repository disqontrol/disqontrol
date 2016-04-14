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
use Symfony\Component\DependencyInjection\Reference;

/**
 * Register failure strategies from the service container in FailureStrategyCollection
 *
 * @author Martin Schlemmer
 */
class FailureStrategiesCompilerPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $repository = $container->findDefinition('failure_strategy_collection');

        $failureStrategies = $container->findTaggedServiceIds(
            'failure_strategy'
        );
        // Add each failure strategy to the repository via the service container
        foreach ($failureStrategies as $id => $tags) {
            $repository->addMethodCall(
                'addFailureStrategy',
                [$id, new Reference($id)]
            );
        }
    }

}
