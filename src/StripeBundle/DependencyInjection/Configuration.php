<?php

declare(strict_types=1);

namespace Tomedio\StripeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('stripe_bundle');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('api_key')
                    ->isRequired()
                    ->info('Your Stripe API key')
                ->end()
                ->scalarNode('webhook_secret')
                    ->isRequired()
                    ->info('Your Stripe webhook signing secret')
                ->end()
                ->scalarNode('success_url')
                    ->isRequired()
                    ->info('URL to redirect after successful payment')
                ->end()
                ->scalarNode('cancel_url')
                    ->isRequired()
                    ->info('URL to redirect after cancelled payment')
                ->end()
                ->arrayNode('subscription_plans')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('id')->isRequired()->end()
                            ->scalarNode('name')->isRequired()->end()
                            ->scalarNode('description')->defaultNull()->end()
                            ->integerNode('amount')->isRequired()->end()
                            ->scalarNode('currency')->defaultValue('usd')->end()
                            ->scalarNode('interval')->defaultValue('month')->end()
                            ->integerNode('trial_period_days')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
