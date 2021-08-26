<?php


namespace Bytes\TwitchClientBundle\Event;

use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Subscription;
use Bytes\TwitchResponseBundle\Objects\Interfaces\UserInterface;
use DateTimeInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class AbstractEventSubSubscriptionEvent
 * @package Bytes\TwitchClientBundle\Event
 */
abstract class AbstractEventSubSubscriptionEvent extends Event
{
    private $entity;

    /**
     * AbstractEventSubSubscriptionEvent constructor.
     * @param EventSubSubscriptionTypes|null $subscriptionType
     * @param UserInterface|null $user
     * @param string|null $callback
     * @param string|null $id
     * @param string|null $status
     * @param DateTimeInterface|null $createdAt
     */
    public function __construct(private ?EventSubSubscriptionTypes $subscriptionType = null, private ?UserInterface $user = null, private ?string $callback = null, private ?string $id = null, private ?string $status = null, private ?DateTimeInterface $createdAt = null)
    {
    }

    /**
     * @return EventSubSubscriptionTypes|null
     */
    public function getSubscriptionType(): ?EventSubSubscriptionTypes
    {
        return $this->subscriptionType;
    }

    /**
     * @param EventSubSubscriptionTypes|null $subscriptionType
     * @return $this
     */
    public function setSubscriptionType(?EventSubSubscriptionTypes $subscriptionType): self
    {
        $this->subscriptionType = $subscriptionType;
        return $this;
    }

    /**
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * @param UserInterface|null $user
     * @return $this
     */
    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCallback(): ?string
    {
        return $this->callback;
    }

    /**
     * @param string|null $callback
     * @return $this
     */
    public function setCallback(?string $callback): self
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     * @return $this
     */
    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     * @return $this
     */
    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param DateTimeInterface|null $createdAt
     * @return $this
     */
    public function setCreatedAt(?DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @param Subscription $subscription
     * @param UserInterface|null $user
     * @return AbstractEventSubSubscriptionEvent
     */
    public static function createFromSubscription(Subscription $subscription, ?UserInterface $user)
    {
        $static = new static();
        $static->setSubscriptionType(EventSubSubscriptionTypes::from($subscription->getType()));
        $static->setUser($user);
        $static->setCallback($subscription->getTransport()?->getCallback());
        $static->setId($subscription->getId());
        $static->setStatus($subscription->getStatus());
        $static->setCreatedAt($subscription->getCreatedAt());

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

//    /**
//     * @param Stream $stream
//     * @param string $type = NotificationTypeEnum::all()[$any]
//     * @param DateTimeInterface|null $notificationTime Notification timestamp. Only used for change and offline types.
//     *
//     * @return $this
//     */
//    public static function create(Stream $stream, string $type, ?DateTimeInterface $notificationTime = null)
//    {
//        return new self($stream, $type, $notificationTime);
//    }
}