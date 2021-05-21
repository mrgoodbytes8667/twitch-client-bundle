<?php

namespace Bytes\TwitchClientBundle\Tests;

use Bytes\ResponseBundle\BytesResponseBundle;
use Bytes\TwitchClientBundle\BytesTwitchClientBundle;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchResponseBundle\BytesTwitchResponseBundle;
use Exception;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class Kernel
 * @package Bytes\TwitchClientBundle\Tests
 */
class Kernel extends BaseKernel
{
    /**
     * @var string
     */
    protected $callback;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    private $classes = [];

    /**
     * Kernel constructor.
     * @param string $callback
     * @param array $config
     */
    public function __construct(string $callback = '', array $config = [])
    {
        $this->callback = $callback;
        $this->config = array_merge([
            'client_id' => Fixture::CLIENT_ID,
            'client_secret' => Fixture::CLIENT_SECRET,
            'hub_secret' => Fixture::HUB_SECRET,
            'user_agent' => Fixture::USER_AGENT,
            'endpoints' => [
                'app' => [
                    'revoke_on_refresh' => false,
                    'fire_revoke_on_refresh' => true,
                ],
                'user' => [
                    'revoke_on_refresh' => false,
                    'fire_revoke_on_refresh' => true,
                ]
            ]
        ], $config);

        parent::__construct('test', true);
    }

    /**
     * @return BundleInterface[]
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new BytesResponseBundle(),
            new BytesTwitchResponseBundle(),
            new BytesTwitchClientBundle(),
        ];
    }

    /**
     * @param LoaderInterface $loader
     * @throws Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->register('security.helper', Security::class);
            $container->register('router.default', UrlGeneratorInterface::class);

            foreach ($this->classes as $class) {
                if(is_array($class)) {
                    if(array_key_exists('id', $class) && array_key_exists('class', $class)) {
                        $container->register($class['id'], $class['class']);
                    } else {
                        $container->register($class[0], $class[1]);
                    }
                } else {
                    $container->register($class);
                }
            }

            if ($this->hasCallback() && !in_array($this->callback, $this->classes)) {
                $container->register($this->callback);

                $container->register('http_client', MockHttpClient::class);

                $container->loadFromExtension('framework', [
                    'http_client' => [
                        'mock_response_factory' => $this->callback,
                    ],
                ]);
            }

            $container->loadFromExtension('bytes_twitch_client', $this->config);
        });
    }

    /**
     * @return bool
     */
    public function hasCallback(): bool
    {
        return !empty($this->callback);
    }

    /**
     * Gets the cache directory.
     *
     * Since Symfony 5.2, the cache directory should be used for caches that are written at runtime.
     * For caches and artifacts that can be warmed at compile-time and deployed as read-only,
     * use the new "build directory" returned by the {@see getBuildDir()} method.
     *
     * @return string The cache directory
     */
    public function getCacheDir()
    {
        return parent::getCacheDir() . '/' . spl_object_hash($this);
    }

    /**
     * @return string
     */
    public function getCallback(): string
    {
        return $this->callback;
    }

    /**
     * @param string $callback
     * @return $this
     */
    public function setCallback(string $callback): self
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function mergeConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * @return string[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @param array $classes
     * @return $this
     */
    public function setClasses(array $classes): self
    {
        $this->classes = $classes;
        return $this;
    }

    /**
     * @param string|array $class
     * @return $this
     */
    public function addClass($class): self
    {
        $this->classes[] = $class;
        return $this;
    }
}
