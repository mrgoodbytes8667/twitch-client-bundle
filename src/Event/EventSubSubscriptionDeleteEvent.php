<?php


namespace Bytes\TwitchClientBundle\Event;


/**
 * Triggered by a successful subscription delete request
 * This should not be manually triggered.
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