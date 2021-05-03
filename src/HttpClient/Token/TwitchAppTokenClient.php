<?php


namespace Bytes\TwitchClientBundle\HttpClient\Token;


use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Event\TokenGrantedEvent;
use Bytes\ResponseBundle\Event\TokenRefreshedEvent;
use Bytes\ResponseBundle\HttpClient\Token\AppTokenClientInterface;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class TwitchAppTokenClient
 * @package Bytes\TwitchClientBundle\HttpClient\Token
 */
class TwitchAppTokenClient extends AbstractTwitchTokenClient implements AppTokenClientInterface
{
    /**
     * Refreshes the provided access token
     * @param AccessTokenInterface|null $token
     * @return AccessTokenInterface|null
     */
    public function refreshToken(AccessTokenInterface $token = null): ?AccessTokenInterface
    {
        return $this->getOrRefreshToken($token);
        // @todo
        // revoke token
    }

    /**
     * Returns an access token
     * @return AccessTokenInterface|null
     */
    public function getToken(): ?AccessTokenInterface
    {
        return $this->getOrRefreshToken(null);
    }

    /**
     * @param AccessTokenInterface|null $token
     * @return AccessTokenInterface|null
     */
    private function getOrRefreshToken(?AccessTokenInterface $token)
    {
        if (empty($token)) {
            $onSuccessCallable = function ($self, $results) {
                /** @var ClientResponseInterface $self */
                /** @var AccessTokenInterface|null $results */
                $this->dispatcher->dispatch(TokenGrantedEvent::new($results));
            };
        } else {
            $onSuccessCallable = function ($self, $results) use ($token) {
                /** @var ClientResponseInterface $self */
                /** @var AccessTokenInterface|null $results */
                $this->dispatcher->dispatch(TokenRefreshedEvent::new($results, $token));
            };
        }
        try {
            return $this->request($this->buildURL('oauth2/token'), type: Token::class, options: ['query' => [
                'grant_type' => 'client_credentials'
            ]], method: HttpMethods::post(), onSuccessCallable: $onSuccessCallable)->deserialize();
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $exception) {
            return null;
        }
    }
}