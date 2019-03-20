<?php

namespace App\DependencyInjection\Compiler;

use App\Manager\AbstractManager;
use App\Manager\ManagerConfigurator;
use App\Manager\ManagerRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ManagerCompilerPass implements CompilerPassInterface {
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container) {
        $registry = $container->getDefinition(ManagerRegistry::class);
        $registry->addMethodCall('setContainer', [new Reference('service_container')]);
        foreach ($container->getServiceIds() as $id) {
            if ($id === AbstractManager::class || $container->hasAlias($id)) {
                // don't brother with the abstract or aliases. the aliased service will be processed
                continue;
            }
            $definition = $container->getDefinition($id);
            // also skip non-autowired or syntetic services
            if ($definition->isSynthetic()) {
                continue;
            }

            // resolve classname
            $className = $container->getParameterBag()->resolveValue($definition->getClass());
            if (strpos($className, 'Doctrine\\') !== 0 && is_subclass_of($className, AbstractManager::class)) {
                $definition->setConfigurator([new Reference(ManagerConfigurator::class), 'configure']);
                // add to registry
                $registry->addMethodCall('add', [$id]);
            }
        }
    }
}