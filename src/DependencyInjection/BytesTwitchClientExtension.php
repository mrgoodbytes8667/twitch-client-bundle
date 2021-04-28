<?php


namespace Bytes\TwitchClientBundle\DependencyInjection;


use Bytes\ResponseBundle\Objects\ConfigNormalizer;
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

        $config = $this->processConfiguration($configuration, $configs);

        $config = ConfigNormalizer::normalizeEndpoints($config, ['eventsub_subscribe', 'app', 'user']);

        $definition = $container->getDefinition('bytes_twitch_client.httpclient.twitch');
        $definition->replaceArgument(3, $config['client_id']);
        $definition->replaceArgument(4, $config['client_secret']);
        $definition->replaceArgument(5, $config['hub_secret']);
        $definition->replaceArgument(6, $config['user_agent']);
        $definition->replaceArgument(7, $config['endpoints']['eventsub_subscribe']['redirects']['route_name']);

        foreach (['bytes_twitch_client.httpclient.twitch.token.user', 'bytes_twitch_client.httpclient.twitch.token.app'] as $value) {
            $definition = $container->getDefinition($value);
            $definition->replaceArgument(1, $config['client_id']);
            $definition->replaceArgument(2, $config['client_secret']);
            $definition->replaceArgument(3, $config['user_agent']);
        }

        foreach (['bytes_twitch_client.oauth.bot', 'bytes_twitch_client.oauth.user'] as $value)
        {
            $definition = $container->getDefinition($value);
            $definition->replaceArgument(2, $config['client_id']);
            $definition->replaceArgument(3, $config['endpoints']);
            $definition->replaceArgument(4, $config['user']);
        }
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

        if (isset($config) && isset($config['hub_secret'])) {
            $config = ['hub_secret' => $config['hub_secret']];
            $container->prependExtensionConfig('bytes_twitch_response', $config);
        }
    }
}
