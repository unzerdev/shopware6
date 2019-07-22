<?php

namespace HeidelPayment\Components\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WebhookCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('heidel_payment.webhooks.factory')) {
            return;
        }

        $definition     = $container->getDefinition('heidel_payment.webhooks.factory');
        $taggedServices = $container->findTaggedServiceIds('heidelpay.webhook_handler');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addWebhookHandler', [
                    new Reference($id),
                    $attributes['hook'],
                ]);
            }
        }
    }
}
