<?php


namespace Bytes\TwitchClientBundle\Event;


use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\Interfaces\UserInterface;

/**
 * Class EventSubSubscriptionCreatePreRequestEvent
 * @package Bytes\TwitchClientBundle\Event
 */
class EventSubSubscriptionCreatePreRequestEvent extends AbstractEventSubSubscriptionEvent
{
    /**
     * @param EventSubSubscriptionTypes $subscriptionType
     * @param UserInterface|null $stream
     * @param string $callback
     * @return static
     */
    public static function make(EventSubSubscriptionTypes $subscriptionType, ?UserInterface $stream, string $callback): static
    {
        $static = new static();
        $static->setSubscriptionType($subscriptionType);
        $static->setUser($stream);
        $static->setCallback($callback);

        return $static;
    }
}