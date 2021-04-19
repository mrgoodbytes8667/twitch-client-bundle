<?php


namespace Bytes\TwitchClientBundle\DependencyInjection;


use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Class BytesTwitchClientExtension
 * @package Bytes\TwitchClientBundle\DependencyInjection
 */
class BytesTwitchClientExtension extends Extension implements ExtensionInterface, PrependExtensionInterface
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);

        /** @var array $config = ['twitch' => ['client_id' => '', 'client_secret' => '', 'hub_secret' => '', 'user_agent' => '', 'eventsub_subscribe_callback_route_name' => '']] */
        $config = $this->processConfiguration($configuration, $configs);

//        $definition = $container->getDefinition('bytes_twitch_client.httpclient.twitch');
//        $definition->replaceArgument(2, $config['twitch']['client_id']);
//        $definition->replaceArgument(3, $config['twitch']['client_secret']);
//        $definition->replaceArgument(4, $config['twitch']['bot_token']);
//        $definition->replaceArgument(5, $config['twitch']['user_agent']);
//
//        $definition = $container->getDefinition('bytes_twitch_client.httpclient.twitch.bot');
//        $definition->replaceArgument(2, $config['twitch']['client_id']);
//        $definition->replaceArgument(3, $config['twitch']['client_secret']);
//        $definition->replaceArgument(4, $config['twitch']['bot_token']);
//        $definition->replaceArgument(5, $config['twitch']['user_agent']);
//
//        $definition = $container->getDefinition('bytes_twitch_client.httpclient.twitch.user');
//        $definition->replaceArgument(2, $config['twitch']['client_id']);
//        $definition->replaceArgument(3, $config['twitch']['client_secret']);
//        $definition->replaceArgument(4, $config['twitch']['user_agent']);
//
//        $definition = $container->getDefinition('bytes_twitch_client.httpclient.twitch.token');
//        $definition->replaceArgument(3, $config['twitch']['client_id']);
//        $definition->replaceArgument(4, $config['twitch']['client_secret']);
//        $definition->replaceArgument(5, $config['twitch']['user_agent']);
    }

    /**
     * Allow an extension to prepend the extension configurations.
     */
    public function prepend(ContainerBuilder $container)
    {
        // process the configuration of this extension
        $configs = $container->getExtensionConfig($this->getAlias());

        // resolve config parameters e.g. %kernel.debug% to its boolean value
        $resolvingBag = $container->getParameterBag();
        $configs = $resolvingBag->resolveValue($configs);

        // use the Configuration class to generate a config array that will be applied to bytes_twitch_response
        /** @var array $config = ['twitch' => ['client_id' => '', 'client_secret' => '', 'hub_secret' => '', 'user_agent' => '', 'eventsub_subscribe_callback_route_name' => '']] */
        $config = $this->processConfiguration(new Configuration(), $configs);

        if (isset($config['twitch']) && isset($config['twitch']['hub_secret'])) {
            $config['twitch'] = ['hub_secret' => $config['twitch']['hub_secret']];
            $container->prependExtensionConfig('bytes_twitch_response', $config);
        }
    }
}