<?php


namespace Bytes\TwitchClientBundle\EventListener;


use Bytes\ResponseBundle\Enums\TokenSource;
use Bytes\ResponseBundle\Event\RevokeTokenEvent;
use Bytes\ResponseBundle\EventListener\AbstractRevokeTokenSubscriber;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchAppTokenClient;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class RevokeTokenSubscriber
 * @package Bytes\TwitchClientBundle\EventListener
 */
class RevokeTokenSubscriber extends AbstractRevokeTokenSubscriber
{
    /**
     * RevokeTokenSubscriber constructor.
     * @param TwitchAppTokenClient $twitchAppTokenClient
     * @param TwitchUserTokenClient $twitchUserTokenClient
     */
    public function __construct(private TwitchAppTokenClient $twitchAppTokenClient, private TwitchUserTokenClient $twitchUserTokenClient)
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
            if ($token->getTokenSource()->equals(TokenSource::user())) {
                $this->twitchUserTokenClient->revokeToken($token);
            } elseif ($token->getTokenSource()->equals(TokenSource::app())) {
                $this->twitchAppTokenClient->revokeToken($token);
            }
        }

        return $event;
    }
}