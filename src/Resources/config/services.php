<?php


namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Bytes\ResponseBundle\Routing\OAuthInterface;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchClientBundle\HttpClient\Retry\TwitchRetryStrategy;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchAppTokenClient;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenClient;
use Bytes\TwitchClientBundle\Routing\TwitchAppOAuth;
use Bytes\TwitchClientBundle\Routing\TwitchUserOAuth;

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
            service('router.default'), // Symfony\Component\Routing\Generator\UrlGeneratorInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['hub_secret']
            '', // $config['user_agent']
            '', // $config['eventsub_subscribe_callback_route_name']
        ])
        ->call('setSerializer', [service('serializer')])
        ->call('setValidator', [service('validator')])
        ->call('setDispatcher', [service('event_dispatcher')])
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
        ->call('setDispatcher', [service('event_dispatcher')])
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
        ->call('setDispatcher', [service('event_dispatcher')])
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

    //region OAuth
    $services->set('bytes_twitch_client.oauth.app', TwitchAppOAuth::class)
        ->args([
            '', // $config['client_id']
            [],
            [] // $config['options']
        ])
        ->call('setUrlGenerator', [service('router.default')]) // Symfony\Component\Routing\Generator\UrlGeneratorInterface
        ->call('setValidator', [service('validator')])
        ->call('setSecurity', [service('security.helper')->ignoreOnInvalid()]) // Symfony\Component\Security\Core\Security
        ->lazy()
        ->alias(TwitchAppOAuth::class, 'bytes_twitch_client.oauth.app')
        ->public();

    $services->set('bytes_twitch_client.oauth.user', TwitchUserOAuth::class)
        ->args([
            '', // $config['client_id']
            [],
            [] // $config['options']
        ])
        ->call('setUrlGenerator', [service('router.default')]) // Symfony\Component\Routing\Generator\UrlGeneratorInterface
        ->call('setValidator', [service('validator')])
        ->call('setSecurity', [service('security.helper')->ignoreOnInvalid()]) // Symfony\Component\Security\Core\Security
        ->lazy()
        ->alias(TwitchUserOAuth::class, 'bytes_twitch_client.oauth.user')
        ->public();

    $services->alias(OAuthInterface::class.' $twitchAppOAuth', TwitchAppOAuth::class);
    $services->alias(OAuthInterface::class.' $twitchUserOAuth', TwitchUserOAuth::class);
    //endregion
};