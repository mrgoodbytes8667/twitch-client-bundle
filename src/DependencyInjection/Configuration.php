<?php


namespace Bytes\TwitchClientBundle\DependencyInjection;

use Bytes\TwitchResponseBundle\Enums\OAuthScopes;
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
            ->ignoreExtraKeys()
            ->children()
                ->scalarNode('client_id')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('The client id for the child bundle')
                    ->defaultValue('')
                ->end()
                ->scalarNode('client_secret')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->info('The client secret for the child bundle')
                    ->defaultValue('')
                ->end()
                ->scalarNode('hub_secret')
                    ->info('The hub secret for the Twitch bundle')
                    ->defaultValue('')
                ->end()
                ->scalarNode('user_agent')
                    ->info('The user agent string for the child bundle (overrides defaults->user_agent). Format must be [Name] ([URL], [VERSION])')
                    ->defaultNull()
                ->end()
                ->booleanNode('user')
                    ->info('Should security be passed to the child OAuth handler?')
                    ->defaultFalse()
                ->end()
                ->scalarNode('login_redirect_route')
                    ->info('Route name for OAuth login')
                    ->defaultValue('')
                ->end() // end login_redirect_route
                ->scalarNode('login_success_route')
                    ->info('Route name for a successful OAuth login')
                    ->defaultValue('')
                ->end() // end login_success_route
                ->arrayNode('endpoints')
                        ->useAttributeAsKey('name')
                        ->arrayPrototype()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('redirects')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->enumNode('method')
                                            ->values(['route_name', 'url'])
                                            ->defaultValue('route_name')
                                        ->end()
                                        ->scalarNode('route_name')->defaultValue('')->end()
                                        ->scalarNode('url')->defaultValue('')->end()
                                    ->end()
                                ->end()
/*                                ->arrayNode('permissions')
                                    ->addDefaultsIfNotSet()
                                    ->info('String constants from the Permissions enum class')
                                    ->children()
                                        ->arrayNode('add')
                                            ->scalarPrototype()
                                                //->beforeNormalization()
                                                //    ->always()
                                                //    ->then(function ($v) { return (new Permissions($v))->value; })
                                                //->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('remove')
                                            ->scalarPrototype()
                                                //->beforeNormalization()
                                                //    ->always()
                                                //    ->then(function ($v) { return (new Permissions($v))->value; })
                                                //->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()*/
                                ->arrayNode('scopes')
                                    ->addDefaultsIfNotSet()
                                    ->info('String constants from the OAuthScopes enum class')
                                    ->children()
                                        ->arrayNode('add')
                                            ->scalarPrototype()
                                                ->beforeNormalization()
                                                    ->always()
                                                    ->then(function ($v) { return (new OAuthScopes($v))->value; })
                                                ->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('remove')
                                            ->scalarPrototype()
                                                ->beforeNormalization()
                                                    ->always()
                                                    ->then(function ($v) { return (new OAuthScopes($v))->value; })
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end() // end scopes
                                ->booleanNode('revoke_on_refresh')
                                    ->info('Should the existing token be revoked when refreshing a token?')
                                    ->defaultFalse()
                                ->end() // end revokeOnRefresh
                                ->booleanNode('fire_revoke_on_refresh')
                                    ->info('Should the existing token fire the revoke event when refreshing a token? Ignored if revokeOnRefresh is true.')
                                    ->defaultFalse()
                                ->end() // end fireRevokeOnRefresh
                            ->end()
                        ->end()
                    //->end()
                ->end()

            ->end();

        return $treeBuilder;
    }
}