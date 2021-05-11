<?php


namespace Bytes\TwitchClientBundle\HttpClient\Token;


use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Event\RevokeTokenEvent;
use Bytes\ResponseBundle\Event\TokenGrantedEvent;
use Bytes\ResponseBundle\Event\TokenRefreshedEvent;
use Bytes\ResponseBundle\Event\TokenRevokedEvent;
use Bytes\ResponseBundle\HttpClient\Token\AppTokenClientInterface;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
use LogicException;
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
     * @param AccessTokenInterface|null $token
     * @return AccessTokenInterface|null
     */
    private function getOrRefreshToken(?AccessTokenInterface $token)
    {
        if (empty($token)) {
            $onDeserializeCallable = function ($self, $results) {
                /** @var ClientResponseInterface $self */
                /** @var AccessTokenInterface|null $results */
                /** @var TokenGrantedEvent $event */
                $event = $this->dispatch(TokenGrantedEvent::new($results));
                return $event->getToken();
            };
        } else {
            $onDeserializeCallable = function ($self, $results) use ($token) {
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
            };
        }
        try {
            return $this->request($this->buildURL('oauth2/token'), type: Token::class, options: ['query' => [
                'grant_type' => 'client_credentials'
            ]], method: HttpMethods::post(), onDeserializeCallable: $onDeserializeCallable)->deserialize();
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $exception) {
            return null;
        }
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
     * Exchanges the provided code for a new access token
     * @param string $code
     * @param string|null $route Either $route or $url is required, $route takes precedence over $url
     * @param string|null|callable(string, array) $url Either $route or $url is required, $route takes precedence over $url
     * @param array $scopes
     * @param callable|null $onSuccessCallable If set, will be triggered if it returns successfully
     * @return AccessTokenInterface|null
     */
    public function exchange(string $code, ?string $route = null, callable|string|null $url = null, array $scopes = [], ?callable $onSuccessCallable = null): ?AccessTokenInterface
    {
        throw new LogicException('Twitch app tokens do not have an exchange protocol');
    }

    /**
     * Return the client name
     * @return string
     */
    public static function getDefaultIndexName(): string
    {
        return 'TWITCH-TOKEN-APP';
    }
}