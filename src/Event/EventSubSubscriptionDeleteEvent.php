<?php


namespace Bytes\TwitchClientBundle\Event;


/**
 * Class EventSubSubscriptionDeleteEvent
 * @package Bytes\TwitchClientBundle\Event
 */
class EventSubSubscriptionDeleteEvent extends AbstractEventSubSubscriptionEvent
{
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