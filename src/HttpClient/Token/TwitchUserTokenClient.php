<?php


namespace Bytes\TwitchClientBundle\HttpClient\Token;


use Bytes\ResponseBundle\Enums\OAuthGrantTypes;
use Bytes\ResponseBundle\Event\RevokeTokenEvent;
use Bytes\ResponseBundle\Event\TokenGrantedEvent;
use Bytes\ResponseBundle\Event\TokenRefreshedEvent;
use Bytes\ResponseBundle\Event\TokenRevokedEvent;
use Bytes\ResponseBundle\HttpClient\Token\UserTokenClientInterface;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\TwitchResponseBundle\Enums\OAuthScopes;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class TwitchUserTokenClient
 * @package Bytes\TwitchClientBundle\HttpClient\Token
 */
class TwitchUserTokenClient extends AbstractTwitchTokenClient implements UserTokenClientInterface
{
    /**
     * Refreshes the provided access token
     * @param AccessTokenInterface|null $token
     * @return AccessTokenInterface|null
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function refreshToken(AccessTokenInterface $token = null): ?AccessTokenInterface
    {
        $tokenString = static::normalizeRefreshToken($token);
        if (empty($tokenString)) {
            return null;
        }
        $redirect = $this->oAuth->getRedirect();
        return $this->tokenExchange($tokenString, url: $redirect, scopes: OAuthScopes::getUserScopes(), grantType: OAuthGrantTypes::refreshToken(),
            onDeserializeCallable: function ($self, $results) use ($token) {
                /** @var ClientResponseInterface $self */
                /** @var AccessTokenInterface|null $results */

                /** @var TokenRefreshedEvent $event */
                $event = $this->dispatch(TokenRefreshedEvent::new($results, $token));

                if($this->revokeOnRefresh) {
                    $this->dispatch(RevokeTokenEvent::new($token));
                } elseif($this->fireRevokeOnRefresh)
                {
                    $this->dispatch(TokenRevokedEvent::new($token));
                }

                return $event->getToken();
            })->deserialize();

    }

    /**
     * Exchanges the provided code (or token) for a (new) access token
     * @param string $code
     * @param string|null $route Either $route or $url (or setOAuth(()) is required, $route takes precedence over $url
     * @param string|null|callable(string, array) $url Either $route or $url (or setOAuth(()) is required, $route takes precedence over $url
     * @param array $scopes
     * @param callable(static, mixed)|null $onSuccessCallable If set, will be triggered if it returns successfully
     * @return AccessTokenInterface|null
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function exchange(string $code, ?string $route = null, string|callable|null $url = null, array $scopes = [], ?callable $onSuccessCallable = null): ?AccessTokenInterface
    {
        $token = $this->tokenExchange($code, $route, $url, $scopes, OAuthGrantTypes::authorizationCode(), onDeserializeCallable: function ($self, $results) {
            /** @var TokenGrantedEvent $event */
            $event = $this->dispatch(TokenGrantedEvent::new($results));
            return $event->getToken();
        }, onSuccessCallable: $onSuccessCallable)
            ->deserialize();

        return $token;
    }

    /**
     * Return the client name
     * @return string
     */
    public static function getDefaultIndexName(): string
    {
        return 'TWITCH-TOKEN-USER';
    }
}