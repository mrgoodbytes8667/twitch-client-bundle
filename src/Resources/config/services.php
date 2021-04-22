<?php


namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchClientBundle\HttpClient\Retry\TwitchRetryStrategy;
use Bytes\TwitchClientBundle\HttpClient\TwitchAppTokenClient;
use Bytes\TwitchClientBundle\HttpClient\TwitchClient;
use Bytes\TwitchClientBundle\HttpClient\TwitchUserTokenClient;

/**
 * @param ContainerConfigurator $container
 */
return static function (ContainerConfigurator $container) {

    $services = $container->services();

    //region Clients
    $services->set('bytes_twitch_client.httpclient.twitch', TwitchClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            service('bytes_twitch_client.httpclient.retry_strategy.twitch'), // Symfony\Component\HttpClient\Retry\RetryStrategyInterface
            service('event_dispatcher'),  // Symfony\Contracts\EventDispatcher\EventDispatcherInterface
            service('router.default'), // Symfony\Component\Routing\Generator\UrlGeneratorInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['hub_secret']
            '', // $config['user_agent']
            '', // $config['eventsub_subscribe_callback_route_name']
        ])
        ->call('setSerializer', [service('serializer')])
        ->call('setValidator', [service('validator')])
        ->call('setResponse', [service('bytes_twitch_client.httpclient.twitch.response')])
        ->lazy()
        ->alias(TwitchClient::class, 'bytes_twitch_client.httpclient.twitch')
        ->public();

    $services->set('bytes_twitch_client.httpclient.twitch.token.user', TwitchUserTokenClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['user_agent']
        ])
        ->call('setSerializer', [service('serializer')])
        ->call('setValidator', [service('validator')])
        ->call('setResponse', [service('bytes_twitch_client.httpclient.twitch.response')])
        ->call('setUrlGenerator', [service('router.default')]) // Symfony\Component\Routing\Generator\UrlGeneratorInterface
        ->lazy()
        ->alias(TwitchUserTokenClient::class, 'bytes_twitch_client.httpclient.twitch.token.user')
        ->public();

    $services->set('bytes_twitch_client.httpclient.twitch.token.app', TwitchAppTokenClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['user_agent']
        ])
        ->call('setSerializer', [service('serializer')])
        ->call('setValidator', [service('validator')])
        ->call('setResponse', [service('bytes_twitch_client.httpclient.twitch.response')])
        ->call('setUrlGenerator', [service('router.default')]) // Symfony\Component\Routing\Generator\UrlGeneratorInterface
        ->lazy()
        ->alias(TwitchAppTokenClient::class, 'bytes_twitch_client.httpclient.twitch.token.app')
        ->public();
    //endregion

    //region Response
    $services->set('bytes_twitch_client.httpclient.twitch.response', TwitchResponse::class)
        ->args([
            service('serializer'), // Symfony\Component\Serializer\SerializerInterface
        ])
        ->call('setDispatcher', [service('event_dispatcher')])
        ->alias(TwitchResponse::class, 'bytes_twitch_client.httpclient.twitch.response')
        ->public();
    //endregion

    //region HttpClient Retry Strategies
    $services->set('bytes_twitch_client.httpclient.retry_strategy.twitch', TwitchRetryStrategy::class)
        ->alias(TwitchRetryStrategy::class, 'bytes_twitch_client.httpclient.retry_strategy.twitch')
        ->public();
    //endregion
};