<?php


namespace Bytes\TwitchClientBundle\HttpClient;


use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\ResponseBundle\Token\Interfaces\AppTokenClientInterface;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class TwitchAppTokenClient
 * @package Bytes\TwitchClientBundle\HttpClient
 */
class TwitchAppTokenClient extends AbstractTwitchTokenClient implements AppTokenClientInterface
{
    /**
     * Refreshes the provided access token
     * @param AccessTokenInterface|string|null $token
     * @return AccessTokenInterface|null
     */
    public function refreshToken(AccessTokenInterface|string $token = null): ?AccessTokenInterface
    {
        return $this->getToken();
        // @todo
        // revoke token
    }

    /**
     * Returns an access token
     * @return AccessTokenInterface|null
     */
    public function getToken(): ?AccessTokenInterface
    {
        try {
            return $this->request($this->buildURL('oauth2/token'), type: Token::class, options: ['query' => [
                'grant_type' => 'client_credentials'
            ]], method: HttpMethods::post())->deserialize();
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $exception) {
            return null;
        }
    }
}