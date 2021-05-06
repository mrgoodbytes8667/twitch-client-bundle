<?php


namespace Bytes\TwitchClientBundle\Event;


use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\Interfaces\UserInterface;
use DateTimeInterface;

/**
 * Class EventSubSubscriptionCreatePostRequestEvent
 * @package Bytes\TwitchClientBundle\Event
 */
class EventSubSubscriptionCreatePostRequestEvent extends AbstractEventSubSubscriptionEvent
{
    /**
     * @param EventSubSubscriptionTypes $subscriptionType
     * @param UserInterface $stream
     * @param string $callback
     * @param string $id
     * @param string $status
     * @param DateTimeInterface $createdAt
     * @return static
     */
    public static function make(EventSubSubscriptionTypes $subscriptionType, UserInterface $stream, string $callback, string $id, string $status, DateTimeInterface $createdAt): static
    {
        $static = new static();
        $static->setSubscriptionType($subscriptionType);
        $static->setUser($stream);
        $static->setCallback($callback);
        $static->setId($id);
        $static->setStatus($status);
        $static->setCreatedAt($createdAt);

        return $static;
    }
}
