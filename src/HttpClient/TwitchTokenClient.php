<?php


namespace Bytes\TwitchClientBundle\HttpClient;


use Bytes\Response\Common\Interfaces\AccessTokenInterface;
use Bytes\ResponseBundle\Enums\OAuthGrantTypes;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class TwitchTokenClient
 * @package Bytes\TwitchClientBundle\HttpClient
 */
class TwitchTokenClient extends TwitchClient
{
    /**
     * @param string $code
     * @param string $redirect Route name
     * @param array $scopes
     * @param OAuthGrantTypes|null $grantType
     * @return AccessTokenInterface|null
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function tokenExchange(string $code, string $redirect, array $scopes = [], OAuthGrantTypes $grantType = null)
    {
        $redirect = $this->urlGenerator->generate($redirect, [], UrlGeneratorInterface::ABSOLUTE_URL);
        return parent::tokenExchange($code, $redirect, $scopes, $grantType);
    }
}