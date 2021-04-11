<?php


namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Bytes\TwitchClientBundle\HttpClient\Retry\TwitchRetryStrategy;
use Bytes\TwitchClientBundle\HttpClient\TwitchBotClient;
use Bytes\TwitchClientBundle\HttpClient\TwitchClient;
use Bytes\TwitchClientBundle\HttpClient\TwitchResponse;
use Bytes\TwitchClientBundle\HttpClient\TwitchTokenClient;
use Bytes\TwitchClientBundle\HttpClient\TwitchUserClient;

/**
 * @param ContainerConfigurator $container
 */
return static function (ContainerConfigurator $container) {

    $services = $container->services();

    //region Clients
    $services->set('bytes_twitch-client.httpclient.twitch', TwitchClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            null, // Symfony\Component\HttpClient\Retry\RetryStrategyInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['bot_token']
            '', // $config['user_agent']
        ])
        ->call('setSerializer', [service('serializer')])
        ->call('setValidator', [service('validator')])
        ->call('setResponse', [service('bytes_twitch-client.httpclient.twitch.response')])
        ->lazy()
        ->alias(TwitchClient::class, 'bytes_twitch-client.httpclient.twitch')
        ->public();

    $services->set('bytes_twitch-client.httpclient.twitch.bot', TwitchBotClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            service('bytes_twitch-client.httpclient.retry_strategy.twitch'), // Symfony\Component\HttpClient\Retry\RetryStrategyInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['bot_token']
            '', // $config['user_agent']
        ])
        ->call('setSerializer', [service('serializer')])
        ->call('setValidator', [service('validator')])
        ->call('setResponse', [service('bytes_twitch-client.httpclient.twitch.response')])
        ->alias(TwitchBotClient::class, 'bytes_twitch-client.httpclient.twitch.bot')
        ->public();

    $services->set('bytes_twitch-client.httpclient.twitch.user', TwitchUserClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            service('bytes_twitch-client.httpclient.retry_strategy.twitch'), // Symfony\Component\HttpClient\Retry\RetryStrategyInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['user_agent']
        ])
        ->call('setSerializer', [service('serializer')])
        ->call('setValidator', [service('validator')])
        ->call('setResponse', [service('bytes_twitch-client.httpclient.twitch.response')])
        ->alias(TwitchUserClient::class, 'bytes_twitch-client.httpclient.twitch.user')
        ->public();

    $services->set('bytes_twitch-client.httpclient.twitch.token', TwitchTokenClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            null, // Symfony\Component\HttpClient\Retry\RetryStrategyInterface
            service('router.default'), // Symfony\Component\Routing\Generator\UrlGeneratorInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['user_agent']
        ])
        ->call('setSerializer', [service('serializer')])
        ->call('setValidator', [service('validator')])
        ->call('setResponse', [service('bytes_twitch-client.httpclient.twitch.response')])
        ->lazy()
        ->alias(TwitchTokenClient::class, 'bytes_twitch-client.httpclient.twitch.token')
        ->public();
    //endregion

    //region Response
    $services->set('bytes_twitch-client.httpclient.twitch.response', TwitchResponse::class)
        ->args([
            service('serializer'), // Symfony\Component\Serializer\SerializerInterface
        ])
        ->alias(TwitchResponse::class, 'bytes_twitch-client.httpclient.twitch.response')
        ->public();
    //endregion

    //region HttpClient Retry Strategies
    $services->set('bytes_twitch-client.httpclient.retry_strategy.twitch', TwitchRetryStrategy::class)
        ->alias(TwitchRetryStrategy::class, 'bytes_twitch-client.httpclient.retry_strategy.twitch')
        ->public();
    //endregion
};