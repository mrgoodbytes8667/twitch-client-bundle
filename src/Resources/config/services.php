<?php


namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Bytes\ResponseBundle\Controller\OAuthController;
use Bytes\ResponseBundle\HttpClient\Token\AppTokenClientInterface;
use Bytes\ResponseBundle\HttpClient\Token\TokenClientInterface;
use Bytes\ResponseBundle\HttpClient\Token\UserTokenClientInterface;
use Bytes\ResponseBundle\Routing\OAuthInterface;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchClientBundle\HttpClient\Retry\TwitchRetryStrategy;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchAppTokenClient;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchAppTokenResponse;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenClient;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenResponse;
use Bytes\TwitchClientBundle\Routing\TwitchAppOAuth;
use Bytes\TwitchClientBundle\Routing\TwitchLoginOAuth;
use Bytes\TwitchClientBundle\Routing\TwitchUserOAuth;
use Bytes\TwitchClientBundle\Security\TwitchOAuthAuthenticator;
use Bytes\TwitchClientBundle\Subscriber\RevokeTokenSubscriber;

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
        ->call('setResponse', [service('bytes_twitch_client.httpclient.response')])
        ->lazy()
        ->alias(TwitchClient::class, 'bytes_twitch_client.httpclient.twitch')
        ->public();
    //endregion

    //region Clients (Tokens)

    $services->set('bytes_twitch_client.httpclient.twitch.token.user', TwitchUserTokenClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['user_agent']
            '', // revoke_on_refresh
            '', // fire_revoke_on_refresh
        ])
        ->call('setSerializer', [service('serializer')])
        ->call('setValidator', [service('validator')])
        ->call('setDispatcher', [service('event_dispatcher')])
        ->call('setResponse', [service('bytes_twitch_client.httpclient.response.token.user')])
        ->call('setUrlGenerator', [service('router.default')]) // Symfony\Component\Routing\Generator\UrlGeneratorInterface
        ->call('setOAuth', [service('bytes_twitch_client.oauth.user')]) // Bytes\TwitchClientBundle\Routing\TwitchUserOAuth
        ->lazy()
        ->alias(TwitchUserTokenClient::class, 'bytes_twitch_client.httpclient.twitch.token.user')
        ->public();

    $services->alias(TokenClientInterface::class.' $twitchUserTokenClient', TwitchUserTokenClient::class);
    $services->alias(UserTokenClientInterface::class.' $twitchTokenClient', TwitchUserTokenClient::class);
    $services->alias(UserTokenClientInterface::class.' $twitchUserTokenClient', TwitchUserTokenClient::class);

    $services->set('bytes_twitch_client.httpclient.twitch.token.app', TwitchAppTokenClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['user_agent']
            '', // revoke_on_refresh
            '', // fire_revoke_on_refresh
        ])
        ->call('setSerializer', [service('serializer')])
        ->call('setValidator', [service('validator')])
        ->call('setDispatcher', [service('event_dispatcher')])
        ->call('setResponse', [service('bytes_twitch_client.httpclient.response.token.app')])
        ->call('setUrlGenerator', [service('router.default')]) // Symfony\Component\Routing\Generator\UrlGeneratorInterface
        ->lazy()
        ->alias(TwitchAppTokenClient::class, 'bytes_twitch_client.httpclient.twitch.token.app')
        ->public();

    $services->alias(TokenClientInterface::class.' $twitchAppTokenClient', TwitchAppTokenClient::class);
    $services->alias(AppTokenClientInterface::class.' $twitchTokenClient', TwitchAppTokenClient::class);
    $services->alias(AppTokenClientInterface::class.' $twitchAppTokenClient', TwitchAppTokenClient::class);
    //endregion

    //region Controllers
    $services->set('bytes_twitch_client.oauth_controller', OAuthController::class)
        ->args([
            service('bytes_twitch_client.oauth.login'), // Bytes\ResponseBundle\Routing\OAuthInterface
            service('router.default'), // Symfony\Component\Routing\Generator\UrlGeneratorInterface
            '', // destination route
        ])
        ->alias(OAuthController::class, 'bytes_twitch_client.oauth_controller')
        ->public();
    //endregion

    //region Response
    $services->set('bytes_twitch_client.httpclient.response', TwitchResponse::class)
        ->args([
            service('serializer'), // Symfony\Component\Serializer\SerializerInterface
            service('event_dispatcher'), // Symfony\Component\Serializer\SerializerInterface
        ])
        ->alias(TwitchResponse::class, 'bytes_twitch_client.httpclient.response')
        ->public();
    
    $services->set('bytes_twitch_client.httpclient.response.token.app', TwitchAppTokenResponse::class)
        ->args([
            service('serializer'), // Symfony\Component\Serializer\SerializerInterface
            service('event_dispatcher'), // Symfony\Component\Serializer\SerializerInterface
        ])
        ->alias(TwitchAppTokenResponse::class, 'bytes_twitch_client.httpclient.response.token.app')
        ->public();

    $services->set('bytes_twitch_client.httpclient.response.token.user', TwitchUserTokenResponse::class)
        ->args([
            service('serializer'), // Symfony\Component\Serializer\SerializerInterface
            service('event_dispatcher'), // Symfony\Component\Serializer\SerializerInterface
        ])
        ->alias(TwitchUserTokenResponse::class, 'bytes_twitch_client.httpclient.response.token.user')
        ->public();
    //endregion

    //region HttpClient Retry Strategies
    $services->set('bytes_twitch_client.httpclient.retry_strategy.twitch', TwitchRetryStrategy::class)
        ->alias(TwitchRetryStrategy::class, 'bytes_twitch_client.httpclient.retry_strategy.twitch')
        ->public();
    //endregion

    //region Routing
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

    $services->set('bytes_twitch_client.oauth.login', TwitchLoginOAuth::class)
        ->args([
            '', // $config['client_id']
            [],
            [] // $config['options']
        ])
        ->call('setUrlGenerator', [service('router.default')]) // Symfony\Component\Routing\Generator\UrlGeneratorInterface
        ->call('setValidator', [service('validator')])
        ->call('setSecurity', [service('security.helper')->ignoreOnInvalid()]) // Symfony\Component\Security\Core\Security
        ->lazy()
        ->alias(TwitchLoginOAuth::class, 'bytes_twitch_client.oauth.login')
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
    $services->alias(OAuthInterface::class.' $twitchLoginOAuth', TwitchLoginOAuth::class);
    $services->alias(OAuthInterface::class.' $twitchUserOAuth', TwitchUserOAuth::class);
    //endregion

    //region Subscribers
    $services->set('bytes_twitch_client.subscriber.revoke_token', RevokeTokenSubscriber::class)
        ->args([
            service('bytes_twitch_client.httpclient.twitch.token.app'),
            service('bytes_twitch_client.httpclient.twitch.token.user'),
        ])
        ->tag('kernel.event_subscriber');
    //endregion

    //region Security
    $services->set('bytes_twitch_client.security.oauth.handler', TwitchOAuthAuthenticator::class)
        ->args([
            service('doctrine.orm.default_entity_manager'),
            service('security.helper'),
            service('router.default'),
            service('bytes_twitch_client.oauth.login'),
            service('bytes_twitch_client.httpclient.twitch.token.user'),
            '',
            ''
        ]);
    //endregion
};