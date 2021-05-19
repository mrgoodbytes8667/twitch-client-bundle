<?php


namespace Bytes\TwitchClientBundle\EventListener;


use Bytes\ResponseBundle\Event\RevokeTokenEvent;
use Bytes\ResponseBundle\EventListener\AbstractRevokeTokenSubscriber;
use Bytes\ResponseBundle\Handler\HttpClientLocator;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class RevokeTokenSubscriber
 * @package Bytes\TwitchClientBundle\EventListener
 */
class RevokeTokenSubscriber extends AbstractRevokeTokenSubscriber
{
    /**
     * RevokeTokenSubscriber constructor.
     * @param HttpClientLocator $httpClientTokenLocator
     */
    public function __construct(private HttpClientLocator $httpClientTokenLocator)
    {
    }

    /**
     * @param RevokeTokenEvent $event
     * @return RevokeTokenEvent
     * @throws TransportExceptionInterface
     */
    public function onRevokeToken(RevokeTokenEvent $event): RevokeTokenEvent
    {
        $token = $event->getToken();
        if ($token->getIdentifier() == 'TWITCH') {
            $client = $this->httpClientTokenLocator->getTokenClient($token->getIdentifier(), $token->getTokenSource());
            $client->revokeToken($token);
        }

        return $event;
    }
}