<?php


namespace Bytes\TwitchClientBundle\HttpClient;


use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Enums\OAuthGrantTypes;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\ResponseBundle\Token\Interfaces\UserTokenClientInterface;
use Bytes\TwitchResponseBundle\Enums\OAuthScopes;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Validate;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use UnexpectedValueException;

/**
 * Class TwitchUserTokenClient
 * @package Bytes\TwitchClientBundle\HttpClient
 */
class TwitchUserTokenClient extends AbstractTwitchTokenClient implements UserTokenClientInterface
{
    /**
     * Refreshes the provided access token
     * @param AccessTokenInterface|string|null $token
     * @return AccessTokenInterface|null
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @todo Handle the route
     */
    public function refreshToken(AccessTokenInterface|string $token = null): ?AccessTokenInterface
    {
        $token = static::normalizeRefreshToken($token);
        if (empty($token)) {
            return null;
        }
        return $this->tokenExchange($token, route: '', scopes: OAuthScopes::getUserScopes(), grantType: OAuthGrantTypes::refreshToken());
    }

    /**
     * Validates the provided access token
     * @param AccessTokenInterface|string $token
     * @return Validate|null
     *
     * @throws UnexpectedValueException
     */
    public function validateToken(AccessTokenInterface|string $token)
    {
        $token = static::normalizeAccessToken($token, false, 'The $token argument is required and cannot be empty.');

        try {
            return $this->request($this->buildURL('oauth2/validate'), type: Validate::class, options: [
                'headers' => [
                    'Authorization' => 'OAuth ' . $token
                ]
            ], method: HttpMethods::get())->deserialize();
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $exception) {
            return null;
        }
    }
}