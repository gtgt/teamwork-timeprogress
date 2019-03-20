<?php
namespace App\DependencyInjection\Compiler;

use Psr\Log\LoggerAwareInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class LoggerAwarePass
 *
 * Helper to set logger for the classes using LoggerAwareInterface
 */
class LoggerAwarePass implements CompilerPassInterface {

    /**
     * @param ContainerBuilder $container
     *
     * @throws \ReflectionException
     */
    public function process(ContainerBuilder $container) {
        $logger = new Reference('logger');

        foreach ($container->getServiceIds() as $id) {
            if ($container->hasAlias($id)) {
                // don't brother with alias. the aliased service will be processed
                continue;
            }
            $definition = $container->getDefinition($id);
            // also skip non-autowired or syntetic services
            if (!$definition->isAutowired() || $definition->isSynthetic()) {
                continue;
            }

            // resolve classname
            $className = $container->getParameterBag()->resolveValue($definition->getClass());

            if (!$r = $container->getReflectionClass($className)) {
                throw new InvalidArgumentException(sprintf('Class "%s" used for service "%s" cannot be found.', $className, $id));
            }
            if ($r->implementsInterface(LoggerAwareInterface::class)) {
                $definition->addMethodCall('setLogger', [$logger]);
            }
        }
    }
}
