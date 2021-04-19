<?php


namespace Bytes\TwitchClientBundle\Event;


/**
 * Class EventSubSubscriptionDeleteEvent
 * @package Bytes\TwitchClientBundle\Event
 */
class EventSubSubscriptionDeleteEvent extends AbstractEventSubSubscriptionEvent
{
    /**
     * @Event("Bytes\TwitchClientBundle\Event\EventSubSubscriptionDeleteEvent")
     */
    public const NAME = 'bytes_twitch_client.eventsub_subscription.delete';

    /**
     * @param string $id
     * @return static
     */
    public static function make(string $id): static
    {
        $static = new static();
        $static->setId($id);

        return $static;
    }
}