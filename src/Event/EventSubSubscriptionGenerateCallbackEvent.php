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
     * @var string
     */
    private $url = '';

    /**
     * EventSubSubscriptionGenerateCallbackEvent constructor.
     * @param EventSubSubscriptionTypes $type
     * @param UserInterface $user
     * @param string $typeKey
     * @param string $userKey
     * @param bool $addLogin
     * @param string $loginKey
     * @param array $extraParameters
     * @param int $referenceType
     */
    public function __construct(EventSubSubscriptionTypes $type, UserInterface $user, string $typeKey = 'type', string $userKey = 'stream', bool $addLogin = true, string $loginKey = 'login', array $extraParameters = [], private int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL)
    {
        $this->parameters = Push::create();
        $this->parameters = $this->parameters->push(value: $type->value, key: $typeKey)
            ->push(value: $user->getUserId(), key: $userKey);

        if ($addLogin) {
            $this->parameters = $this->parameters->push(value: $user->getLogin(), key: $loginKey);
        }

        if (!empty($extraParameters)) {
            foreach ($extraParameters as $key => $value) {
                $this->parameters = $this->parameters->push(value: $value, key: $key);
            }
        }
    }

    /**
     * @param EventSubSubscriptionTypes $type
     * @param UserInterface $user
     * @param string $typeKey
     * @param string $userKey
     * @param bool $addLogin
     * @param string $loginKey
     * @param array $extraParameters
     * @param int $referenceType
     * @return static
     */
    public static function new(EventSubSubscriptionTypes $type, UserInterface $user, string $typeKey = 'type', string $userKey = 'stream', bool $addLogin = true, string $loginKey = 'login', array $extraParameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): static
    {
        return new static(type: $type, user: $user, typeKey: $typeKey, userKey: $userKey, addLogin: $addLogin, loginKey: $loginKey, extraParameters: $extraParameters, referenceType: $referenceType);
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters->value();
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
            $parameters = new Push();
            foreach ($parameters as $key => $value) {
                $parameters = $this->parameters->push(value: $value, key: $key);
            }
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
}