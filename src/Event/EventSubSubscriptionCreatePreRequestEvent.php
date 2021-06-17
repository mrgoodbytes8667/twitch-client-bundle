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
    private $entity;

    /**
     * @param EventSubSubscriptionTypes $subscriptionType
     * @param UserInterface $stream
     * @param string $callback
     * @return static
     */
    public static function make(EventSubSubscriptionTypes $subscriptionType, UserInterface $stream, string $callback): static
    {
        $static = new static();
        $static->setSubscriptionType($subscriptionType);
        $static->setUser($stream);
        $static->setCallback($callback);

        return $static;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param $entity
     * @return $this
     */
    public function setEntity($entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasEntity(): bool {
        return !is_null($this->entity);
    }
}