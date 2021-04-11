<?php


namespace Bytes\TwitchClientBundle\DependencyInjection;


use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * Class BytesTwitchClientExtension
 * @package Bytes\TwitchClientBundle\DependencyInjection
 */
class BytesTwitchClientExtension extends Extension implements ExtensionInterface
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

        /** @var array $config = ['client_id' => '', 'client_secret' => '', 'client_public_key' => '', 'bot_token' => '', 'user' => false, 'redirects' => ['bot' => ['method' => '', 'route_name' => '', 'url' => '']], 'user' => ['method' => '', 'route_name' => '', 'url' => '']], 'slash' => ['method' => '', 'route_name' => '', 'url' => '']], 'login' => ['method' => '', 'route_name' => '', 'url' => '']]] */
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition('bytes_twitch-client.oauth');
        $definition->replaceArgument(2, $config['client_id']);
        $definition->replaceArgument(3, $config['endpoints']);
        $definition->replaceArgument(4, $config['user']);

        $definition = $container->getDefinition('bytes_twitch-client.httpclient.twitch');
        $definition->replaceArgument(2, $config['client_id']);
        $definition->replaceArgument(3, $config['client_secret']);
        $definition->replaceArgument(4, $config['bot_token']);
        $definition->replaceArgument(5, $config['user_agent']);

        $definition = $container->getDefinition('bytes_twitch-client.httpclient.twitch.bot');
        $definition->replaceArgument(2, $config['client_id']);
        $definition->replaceArgument(3, $config['client_secret']);
        $definition->replaceArgument(4, $config['bot_token']);
        $definition->replaceArgument(5, $config['user_agent']);

        $definition = $container->getDefinition('bytes_twitch-client.httpclient.twitch.user');
        $definition->replaceArgument(2, $config['client_id']);
        $definition->replaceArgument(3, $config['client_secret']);
        $definition->replaceArgument(4, $config['user_agent']);

        $definition = $container->getDefinition('bytes_twitch-client.httpclient.twitch.token');
        $definition->replaceArgument(3, $config['client_id']);
        $definition->replaceArgument(4, $config['client_secret']);
        $definition->replaceArgument(5, $config['user_agent']);
    }
}