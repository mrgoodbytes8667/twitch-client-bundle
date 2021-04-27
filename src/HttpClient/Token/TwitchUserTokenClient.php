<?php


namespace Bytes\TwitchClientBundle\HttpClient\Token;


use Bytes\ResponseBundle\Enums\OAuthGrantTypes;
use Bytes\ResponseBundle\Event\TokenChangedEvent;
use Bytes\ResponseBundle\HttpClient\Token\UserTokenClientInterface;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\TwitchResponseBundle\Enums\OAuthScopes;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
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
     * @var string
     */
    protected static $tokenResponseClass = TwitchUserTokenResponse::class;

    /**
     * Refreshes the provided access token
     * @param AccessTokenInterface|null $token
     * @return AccessTokenInterface|null
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @todo Handle the route
     */
    public function refreshToken(AccessTokenInterface $token = null): ?AccessTokenInterface
    {
        $token = static::normalizeRefreshToken($token);
        if (empty($token)) {
            return null;
        }
        return $this->tokenExchange($token, route: '', scopes: OAuthScopes::getUserScopes(), grantType: OAuthGrantTypes::refreshToken(), onSuccessCallable: function ($self, $results) use ($token) {
            /** @var ClientResponseInterface $self */
            /** @var AccessTokenInterface|null $results */
            $this->dispatcher->dispatch(TokenChangedEvent::new($results, Token::createFromAccessToken($token)));
        });

    }
}