<?php


namespace Bytes\TwitchClientBundle\Event;


use Bytes\ResponseBundle\Objects\Push;
use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\Interfaces\UserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class EventSubSubscriptionGenerateCallbackEvent
 * @package Bytes\TwitchClientBundle\Event
 */
class EventSubSubscriptionGenerateCallbackEvent extends Event
{
    /**
     * @var Push
     */
    private $parameters;

    /**
     * @var bool
     */
    private bool $generationSkipped = false;

    /**
     * EventSubSubscriptionGenerateCallbackEvent constructor.
     * @param string|null $callbackName
     * @param bool $addType
     * @param string $typeKey
     * @param string $userKey
     * @param bool $addLogin
     * @param string $loginKey
     * @param int|null $referenceType
     * @param string|null $url
     * @param EventSubSubscriptionTypes|null $type
     * @param UserInterface|null $user
     */
    public function __construct(private ?string $callbackName = null, private bool $addType = true, private string $typeKey = 'type', private string $userKey = 'stream', private bool $addLogin = true, private string $loginKey = 'login', private ?int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL, private ?string $url = null, private ?EventSubSubscriptionTypes $type = null, private ?UserInterface $user = null)
    {
        if (!empty($url)) {
            $this->generationSkipped = true;
        }
        if (is_null($referenceType)) {
            $this->referenceType = UrlGeneratorInterface::ABSOLUTE_URL;
        }
        $this->parameters = Push::create();
    }

    /**
     * @param string $callbackName
     * @param EventSubSubscriptionTypes $type
     * @param UserInterface $user
     * @param bool $addType
     * @param string $typeKey
     * @param string $userKey
     * @param bool $addLogin
     * @param string $loginKey
     * @param array $extraParameters
     * @param int $referenceType
     * @return static
     */
    public static function new(string $callbackName, EventSubSubscriptionTypes $type, UserInterface $user, bool $addType = true, string $typeKey = 'type', string $userKey = 'stream', bool $addLogin = true, string $loginKey = 'login', array $extraParameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): static
    {
        $static = new static(callbackName: $callbackName, addType: $addType, typeKey: $typeKey, userKey: $userKey, addLogin: $addLogin, loginKey: $loginKey, referenceType: $referenceType, type: $type, user: $user);
        $static->populate(type: $type, user: $user, addType: $addType, typeKey: $typeKey, userKey: $userKey, addLogin: $addLogin, loginKey: $loginKey, extraParameters: $extraParameters);
        return $static;
    }

    /**
     * @param EventSubSubscriptionTypes $type
     * @param UserInterface $user
     * @param bool $addType
     * @param string $typeKey
     * @param string $userKey
     * @param bool $addLogin
     * @param string $loginKey
     * @param array $extraParameters
     * @return $this
     */
    public function populate(EventSubSubscriptionTypes $type, UserInterface $user, bool $addType = true, string $typeKey = 'type', string $userKey = 'stream', bool $addLogin = true, string $loginKey = 'login', array $extraParameters = []): self
    {
        $parameters = Push::create();

        if ($addType) {
            $parameters = $parameters->push(value: $type->value, key: $typeKey);
        }

        $parameters = $parameters->push(value: $user->getUserId(), key: $userKey);

        if ($addLogin) {
            $parameters = $parameters->push(value: $user->getLogin(), key: $loginKey);
        }

        if (!empty($extraParameters)) {
            foreach ($extraParameters as $key => $value) {
                $parameters = $parameters->push(value: $value, key: $key);
            }
        }
        $this->setParameters($parameters);
        return $this;
    }

    /**
     * @param EventSubSubscriptionGenerateCallbackEvent $event
     * @param EventSubSubscriptionTypes $type
     * @param UserInterface $user
     * @return static
     */
    public static function from(EventSubSubscriptionGenerateCallbackEvent $event, EventSubSubscriptionTypes $type, UserInterface $user): static
    {
        $static = clone $event;
        $static->setType($type)
            ->setUser($user)
            ->populate(type: $type, user: $user, addType: $static->getAddType(), typeKey: $static->getTypeKey(), userKey: $static->getUserKey(), addLogin: $static->getAddLogin(), loginKey: $static->getLoginKey());
        return $static;
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
     * @param EventSubSubscriptionTypes|null $type
     * @return $this
     */
    public function setType(?EventSubSubscriptionTypes $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getTypeKey(): string
    {
        return $this->typeKey;
    }

    /**
     * @return string
     */
    public function getUserKey(): string
    {
        return $this->userKey;
    }

    /**
     * @return bool
     */
    public function getAddLogin(): bool
    {
        return $this->addLogin;
    }

    /**
     * @return string
     */
    public function getLoginKey(): string
    {
        return $this->loginKey;
    }

    /**
     * @return bool
     */
    public function getAddType(): bool
    {
        return $this->addType;
    }

    /**
     * @param bool $addType
     * @return $this
     */
    public function setAddType(bool $addType): self
    {
        $this->addType = $addType;
        return $this;
    }

    /**
     * @return bool
     */
    public function isGenerationSkipped(): bool
    {
        return $this->generationSkipped;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters?->value() ?? [];
    }

    /**
     * @param Push|array|null $parameters
     * @return $this
     */
    public function setParameters(Push|array|null $parameters): self
    {
        if (empty($parameters)) {
            $parameters = new Push();
        } elseif (is_array($parameters)) {
            $temp = new Push();
            foreach ($parameters as $key => $value) {
                $temp = $temp->push(value: $value, key: $key);
            }
            $parameters = $temp;
            unset($temp);
        }
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @param int|string $key
     * @param $value
     * @return $this
     */
    public function addParameter(int|string $key, $value): self
    {
        $this->parameters = $this->parameters->push(value: $value, key: $key);
        return $this;
    }

    /**
     * @param int|string $key
     * @return $this
     */
    public function removeParameter(int|string $key): self
    {
        $this->parameters = $this->parameters->removeKey($key);
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasUrl(): bool
    {
        return !empty($this->url);
    }

    /**
     * @return string|null
     */
    public function getCallbackName(): ?string
    {
        return $this->callbackName;
    }

    /**
     * @param string|null $callbackName
     * @return $this
     */
    public function setCallbackName(?string $callbackName): self
    {
        $this->callbackName = $callbackName;
        return $this;
    }

    /**
     * @return EventSubSubscriptionTypes|null
     */
    public function getType(): ?EventSubSubscriptionTypes
    {
        return $this->type;
    }

    /**
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * @param string $typeKey
     * @return $this
     */
    public function setTypeKey(string $typeKey): self
    {
        $this->typeKey = $typeKey;
        return $this;
    }

    /**
     * @param string $userKey
     * @return $this
     */
    public function setUserKey(string $userKey): self
    {
        $this->userKey = $userKey;
        return $this;
    }

    /**
     * @param bool $addLogin
     * @return $this
     */
    public function setAddLogin(bool $addLogin): self
    {
        $this->addLogin = $addLogin;
        return $this;
    }

    /**
     * @param string $loginKey
     * @return $this
     */
    public function setLoginKey(string $loginKey): self
    {
        $this->loginKey = $loginKey;
        return $this;
    }

    /**
     * @return int
     */
    public function getReferenceType(): int
    {
        return $this->referenceType;
    }

    /**
     * @param int $referenceType
     * @return $this
     */
    public function setReferenceType(int $referenceType): self
    {
        $this->referenceType = $referenceType;
        return $this;
    }
}