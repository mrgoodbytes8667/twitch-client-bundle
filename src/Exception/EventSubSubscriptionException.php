<?php


namespace Bytes\TwitchClientBundle\Exception;


use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Interfaces\SubscriptionInterface;
use Bytes\TwitchResponseBundle\Objects\Interfaces\UserInterface;
use DateTimeInterface;
use Exception;
use Throwable;

/**
 * Class EventSubSubscriptionException
 * Generic EventSubSubscription exceptions
 * @package Bytes\TwitchClientBundle\Exception
 */
class EventSubSubscriptionException extends Exception
{
    /**
     * EventSubSubscriptionException constructor. Note: The message is NOT binary safe.
     * @param string $message [optional] The Exception message to throw.
     * @param SubscriptionInterface|null $subscription
     * @param EventSubSubscriptionTypes|null $subscriptionType
     * @param UserInterface|null $user
     * @param string|null $callback
     * @param string|null $id
     * @param string|null $status
     * @param DateTimeInterface|null $createdAt
     * @param int $code [optional] The Exception code.
     * @param Throwable|null $previous [optional] The previous throwable used for the exception chaining.
     */
    public function __construct($message = "", ?SubscriptionInterface $subscription = null, private ?EventSubSubscriptionTypes $subscriptionType = null, private ?UserInterface $user = null, private ?string $callback = null, private ?string $id = null, private ?string $status = null, private ?DateTimeInterface $createdAt = null, $code = 0, Throwable $previous = null)
    {
        if(!empty($subscription))
        {
            if(empty($subscriptionType) && !empty($subscription->getType())) {
                $this->subscriptionType = $subscription->getType() instanceof EventSubSubscriptionTypes ? $subscription->getType() : EventSubSubscriptionTypes::from($subscription->getType());
            }
            if(empty($callback)) {
                $this->callback = $subscription->getCallback();
            }
            if(empty($id)) {
                $this->id = $subscription->getEventSubId();
            }
            if(empty($status)) {
                $this->status = $subscription->getStatus();
            }
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return EventSubSubscriptionTypes|null
     */
    public function getSubscriptionType(): ?EventSubSubscriptionTypes
    {
        return $this->subscriptionType;
    }

    /**
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * @return string|null
     */
    public function getCallback(): ?string
    {
        return $this->callback;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }
}