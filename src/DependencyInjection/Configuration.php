<?php


namespace Bytes\TwitchClientBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 * @package Bytes\TwitchClientBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('bytes_twitch_client');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('client_id')->defaultValue('')->end()
            ->scalarNode('client_secret')->defaultValue('')->end()
            ->scalarNode('hub_secret')->defaultValue('')->end()
            ->scalarNode('user_agent')->defaultNull()->end()
            ->scalarNode('eventsub_subscribe_callback_route_name')->defaultNull()->end()
            ->end();

        return $treeBuilder;
    }
}