<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class StripeBundleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('stripe_bundle.api_key', $config['api_key']);
        $container->setParameter('stripe_bundle.webhook_secret', $config['webhook_secret']);
        $container->setParameter('stripe_bundle.success_url', $config['success_url']);
        $container->setParameter('stripe_bundle.cancel_url', $config['cancel_url']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'stripe_bundle';
    }
}
