<?php


namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Bytes\ResponseBundle\Controller\OAuthController;
use Bytes\ResponseBundle\HttpClient\Token\AppTokenClientInterface;
use Bytes\ResponseBundle\HttpClient\Token\TokenClientInterface;
use Bytes\ResponseBundle\HttpClient\Token\UserTokenClientInterface;
use Bytes\ResponseBundle\Routing\OAuthInterface;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionGenerateCallbackEvent;
use Bytes\TwitchClientBundle\EventListener\EventSubSubscriptionGenerateCallbackSubscriber;
use Bytes\TwitchClientBundle\EventListener\RevokeTokenSubscriber;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchEventSubClient;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchClientBundle\HttpClient\Retry\TwitchRetryStrategy;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchAppTokenClient;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchAppTokenResponse;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenClient;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenResponse;
use Bytes\TwitchClientBundle\Request\TwitchUserConverter;
use Bytes\TwitchClientBundle\Routing\TwitchAppOAuth;
use Bytes\TwitchClientBundle\Routing\TwitchLoginOAuth;
use Bytes\TwitchClientBundle\Routing\TwitchUserOAuth;
use Bytes\TwitchClientBundle\Security\Voters\TwitchHubSignatureVoter;

/**
 * @param ContainerConfigurator $container
 */
return static function (ContainerConfigurator $container) {

    $services = $container->services();

    //region Clients
    $services->set('bytes_twitch_client.httpclient.twitch', TwitchClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            service('event_dispatcher'),
            service('bytes_twitch_client.httpclient.retry_strategy.twitch'), // Symfony\Component\HttpClient\Retry\RetryStrategyInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['user_agent']
        ])
        ->call('setResponse', [service('bytes_twitch_client.httpclient.response')])
        ->tag('bytes_response.http_client')
        ->tag('bytes_response.http_client.api')
        ->alias(TwitchClient::class, 'bytes_twitch_client.httpclient.twitch')
        ->public();

    $services->set('bytes_twitch_client.httpclient.twitch.eventsub', TwitchEventSubClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            service('event_dispatcher'),
            service('bytes_twitch_client.httpclient.retry_strategy.twitch'), // Symfony\Component\HttpClient\Retry\RetryStrategyInterface
            service('router.default'), // Symfony\Component\Routing\Generator\UrlGeneratorInterface
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['hub_secret']
            '', // $config['user_agent']
            '', // $config['eventsub_subscribe_callback_route_name']
        ])
        ->call('setResponse', [service('bytes_twitch_client.httpclient.response')])
        ->call('setEventSubSubscriptionGenerateCallbackEvent', [service('bytes_twitch_client.event.generate_callback')])
        ->tag('bytes_response.http_client')
        ->tag('bytes_response.http_client.api')
        ->alias(TwitchEventSubClient::class, 'bytes_twitch_client.httpclient.twitch.eventsub')
        ->public();
    //endregion

    //region Clients (Tokens)

    $services->set('bytes_twitch_client.httpclient.twitch.token.user', TwitchUserTokenClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            service('event_dispatcher'),
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['user_agent']
            '', // revoke_on_refresh
            '', // fire_revoke_on_refresh
        ])
        ->call('setResponse', [service('bytes_twitch_client.httpclient.response.token.user')])
        ->call('setOAuth', [service('bytes_twitch_client.oauth.user')]) // Bytes\TwitchClientBundle\Routing\TwitchUserOAuth
        ->tag('bytes_response.http_client')
        ->tag('bytes_response.http_client.token')
        ->lazy()
        ->alias(TwitchUserTokenClient::class, 'bytes_twitch_client.httpclient.twitch.token.user')
        ->public();

    $services->alias(TokenClientInterface::class.' $twitchUserTokenClient', TwitchUserTokenClient::class);
    $services->alias(UserTokenClientInterface::class.' $twitchTokenClient', TwitchUserTokenClient::class);
    $services->alias(UserTokenClientInterface::class.' $twitchUserTokenClient', TwitchUserTokenClient::class);

    $services->set('bytes_twitch_client.httpclient.twitch.token.app', TwitchAppTokenClient::class)
        ->args([
            service('http_client'), // Symfony\Contracts\HttpClient\HttpClientInterface
            service('event_dispatcher'),
            '', // $config['client_id']
            '', // $config['client_secret']
            '', // $config['user_agent']
            '', // revoke_on_refresh
            '', // fire_revoke_on_refresh
        ])
        ->call('setResponse', [service('bytes_twitch_client.httpclient.response.token.app')])
        ->tag('bytes_response.http_client')
        ->tag('bytes_response.http_client.token')
        ->lazy()
        ->alias(TwitchAppTokenClient::class, 'bytes_twitch_client.httpclient.twitch.token.app')
        ->public();

    $services->alias(TokenClientInterface::class.' $twitchAppTokenClient', TwitchAppTokenClient::class);
    $services->alias(AppTokenClientInterface::class.' $twitchTokenClient', TwitchAppTokenClient::class);
    $services->alias(AppTokenClientInterface::class.' $twitchAppTokenClient', TwitchAppTokenClient::class);
    //endregion

    //region Controllers
    foreach (['app', 'login', 'user'] as $type) {
        $services->set(sprintf('bytes_twitch_client.oauth_controller.%s', $type), OAuthController::class)
            ->args([
                service(sprintf('bytes_twitch_client.oauth.%s', $type)), // Bytes\ResponseBundle\Routing\OAuthInterface
                service('router.default'), // Symfony\Component\Routing\Generator\UrlGeneratorInterface
                '', // destination route
            ])
            ->public();
    }
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
        ->call('setCsrfTokenManager', [service('security.csrf.token_manager')])
        ->tag('bytes_response.oauth')
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
        ->call('setCsrfTokenManager', [service('security.csrf.token_manager')])
        ->tag('bytes_response.oauth')
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
        ->call('setCsrfTokenManager', [service('security.csrf.token_manager')])
        ->tag('bytes_response.oauth')
        ->lazy()
        ->alias(TwitchUserOAuth::class, 'bytes_twitch_client.oauth.user')
        ->public();

    $services->alias(OAuthInterface::class.' $twitchAppOAuth', TwitchAppOAuth::class);
    $services->alias(OAuthInterface::class.' $twitchLoginOAuth', TwitchLoginOAuth::class);
    $services->alias(OAuthInterface::class.' $twitchUserOAuth', TwitchUserOAuth::class);
    //endregion

    //region Voters
    $services->set('bytes_twitch_client.security.voter.signature', TwitchHubSignatureVoter::class)
        ->args([
            service('bytes_twitch_response.signature.eventsub') // \Bytes\TwitchResponseBundle\Request\EventSubSignature
        ])
        ->tag('security.voter')
        ->lazy();
    //endregion

    //region Converters
    $services->set('bytes_twitch_client.twitch_user_converter', TwitchUserConverter::class)
        ->args([
            service('bytes_twitch_client.httpclient.twitch'),
        ])
        ->tag('request.param_converter', [
            'converter' => 'bytes_twitch_client_user',
            'priority' => false,
        ]);
    //endregion

    //region Subscribers
    $services->set('bytes_twitch_client.subscriber.revoke_token', RevokeTokenSubscriber::class)
        ->args([
            service('bytes_response.locator.http_client.token'),
        ])
        ->tag('kernel.event_subscriber');

    $services->set('bytes_twitch_client.event.generate_callback', EventSubSubscriptionGenerateCallbackEvent::class)
        ->args([
            '', //string|null $callbackName
            '', // bool $addType
            '', //string $typeKey
            '', //string $userKey
            '', //bool $addLogin
            '', //string $loginKey
            '', //int $referenceType
            '', //string $url
        ])
        ->alias(EventSubSubscriptionGenerateCallbackEvent::class, 'bytes_twitch_client.event.generate_callback')
        ->public();

    $services->set('bytes_twitch_client.subscriber.generate_callback', EventSubSubscriptionGenerateCallbackSubscriber::class)
        ->args([
            service('router.default'), // Symfony\Component\Routing\Generator\UrlGeneratorInterface
        ])
        ->tag('kernel.event_subscriber');
    //endregion
};