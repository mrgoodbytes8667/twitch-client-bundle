<?php


namespace Bytes\TwitchClientBundle\HttpClient\Api;


use Bytes\ResponseBundle\Annotations\Auth;
use Bytes\ResponseBundle\Annotations\Client;
use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Event\ObtainValidTokenEvent;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Interfaces\IdInterface;
use Bytes\ResponseBundle\Objects\IdNormalizer;
use Bytes\ResponseBundle\Objects\Push;
use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionCreatePostRequestEvent;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionCreatePreRequestEvent;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionDeleteEvent;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionGenerateCallbackEvent;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchEventSubGetSubscriptionsResponse;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchResponseBundle\Enums\EventSubStatus;
use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Condition;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Create;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Subscription;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Subscriptions;
use Bytes\TwitchResponseBundle\Objects\Interfaces\UserInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class TwitchEventSubClient
 * @package Bytes\TwitchClientBundle\HttpClient\Api
 *
 * @Client(identifier="TWITCH", tokenSource="app")
 */
class TwitchEventSubClient extends AbstractTwitchClient
{
    /**
     * TwitchEventSubClient constructor.
     * @param HttpClientInterface $httpClient
     * @param RetryStrategyInterface|null $strategy
     * @param UrlGeneratorInterface $urlGenerator
     * @param string $clientId
     * @param string $clientSecret
     * @param string $hubSecret
     * @param string|null $userAgent
     * @param string|null $callbackName
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     */
    public function __construct(HttpClientInterface $httpClient, EventDispatcherInterface $dispatcher, ?RetryStrategyInterface $strategy, protected UrlGeneratorInterface $urlGenerator, string $clientId, string $clientSecret, protected string $hubSecret, ?string $userAgent, protected ?string $callbackName = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null, protected ?EventSubSubscriptionGenerateCallbackEvent $eventSubSubscriptionGenerateCallbackEvent = null)
    {
        parent::__construct($httpClient, $dispatcher, $strategy, $clientId, $clientSecret, $userAgent, $defaultOptionsByRegexp, $defaultRegexp);
    }

    /**
     * Return the client name
     * @return string
     */
    public static function getDefaultIndexName(): string
    {
        return 'TWITCH-EVENTSUB';
    }

    /**
     * @param EventSubSubscriptionTypes $type
     * @param UserInterface $stream
     * @param null $callback When null, triggers an EventSubSubscriptionGenerateCallbackEvent event which will generate a full url. Add your own listener/subscriber to add extra parameters or completely replace the built in subscriber.
     * @param array $extraConditions Placeholder for future types
     * @return ClientResponseInterface
     * @throws TransportExceptionInterface
     * @throws NoTokenException
     */
    public function eventSubSubscribe(EventSubSubscriptionTypes $type, UserInterface $stream, $callback = null, $extraConditions = []): ClientResponseInterface
    {
        $conditions = new Condition();
        switch ($type) {
            case EventSubSubscriptionTypes::channelUpdate():
            case EventSubSubscriptionTypes::streamOnline():
            case EventSubSubscriptionTypes::streamOffline():
            case EventSubSubscriptionTypes::channelSubscribe():
            case EventSubSubscriptionTypes::channelChannelPointsCustomRewardAdd():
                $conditions->setBroadcasterUserId($stream->getUserId());
                break;
            case EventSubSubscriptionTypes::userUpdate():
                $conditions->setUserId($stream->getUserId());
                break;
            default:
                throw new InvalidArgumentException(sprintf('The type "%s" is not yet supported', $type));
                break;
        }

        if (is_string($callback)) {
            $url = $callback;
        } elseif (is_null($callback)) {
            $event = $this->dispatchEventSubSubscriptionGenerateCallbackEvent(type: $type, user: $stream);
            $url = $event->getUrl();
        } elseif (is_callable($callback)) {
            $url = call_user_func($callback, $type, $stream);
        } else {
            throw new InvalidArgumentException('The argument "callback" must be a valid URL, a callback, or null.');
        }

        $create = Create::create($type, $conditions, $url, $this->hubSecret);
        $json = $this->serializer->serialize($create, 'json', [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]);
        $params['body'] = $json;

        /**
         *
         */
        $event = $this->dispatchEventSubSubscriptionCreatePreRequestEvent($type, $stream, $url);
        if (!$event->hasEntity()) {
            throw new Exception('Unable to save new subscription');
        }
        return $this->jsonRequest(url: 'eventsub/subscriptions', caller: __METHOD__, type: Subscriptions::class,
            options: $params, method: HttpMethods::post(), onSuccessCallable: function ($self, $subscriptions) {
                /** @var TwitchResponse $self */
                if (array_key_exists('user', $self->getExtraParams())) {
                    $user = $self->getExtraParams()['user'];
                }
                $this->dispatchEventSubSubscriptionCreatePostRequestEvent($subscriptions->getSubscription(), $user);
            }, params: ['user' => $stream]);
    }

    /**
     * @param EventSubSubscriptionTypes $type
     * @param UserInterface $user
     * @return EventSubSubscriptionGenerateCallbackEvent
     */
    protected function dispatchEventSubSubscriptionGenerateCallbackEvent(EventSubSubscriptionTypes $type, UserInterface $user): EventSubSubscriptionGenerateCallbackEvent
    {
        return $this->dispatch(EventSubSubscriptionGenerateCallbackEvent::from($this->eventSubSubscriptionGenerateCallbackEvent, $type, $user));
    }

    /**
     * @param EventSubSubscriptionTypes $subscriptionType
     * @param UserInterface $stream
     * @param string $callback
     * @return EventSubSubscriptionCreatePreRequestEvent
     */
    protected function dispatchEventSubSubscriptionCreatePreRequestEvent(EventSubSubscriptionTypes $subscriptionType, UserInterface $stream, string $callback)
    {
        return $this->dispatch(EventSubSubscriptionCreatePreRequestEvent::make($subscriptionType, $stream, $callback));
    }

    /**
     * @param Subscription $subscriptionType
     * @param UserInterface $stream
     * @return EventSubSubscriptionCreatePostRequestEvent
     */
    protected function dispatchEventSubSubscriptionCreatePostRequestEvent(Subscription $subscriptionType, UserInterface $stream)
    {
        return $this->dispatch(EventSubSubscriptionCreatePostRequestEvent::createFromSubscription($subscriptionType, $stream));
    }

    /**
     * Get EventSub Subscriptions
     * Get a list of your EventSub subscriptions. The subscriptions are paginated and ordered by most recent first.
     * @param EventSubStatus|EventSubSubscriptionTypes|null $filter
     * @param bool $throw
     * @param string|null $before
     * @param string|null $after
     * @param bool $followPagination
     * @return ClientResponseInterface
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     *
     * @link https://dev.twitch.tv/docs/api/reference#get-eventsub-subscriptions
     */
    public function eventSubGetSubscriptions(EventSubStatus|EventSubSubscriptionTypes|null $filter = null, bool $throw = true, ?string $before = null, ?string $after = null, bool $followPagination = true): ClientResponseInterface
    {
        $options = [];
        $query = Push::create();
        $responseFilter = null;
        if (!empty($filter)) {
            if ($filter instanceof EventSubStatus) {
                $query = $query->push($filter->value, 'status');
            } elseif ($filter instanceof EventSubSubscriptionTypes) {
                $query = $query->push($filter->value, 'type');
            }
            $responseFilter = $filter;
        }
        $query = $query->push($before, 'before')
            ->push($after, 'after');

        if(!empty($query->value())) {
            $options['query'] = $query->value();
        }
        return $this->request(url: 'eventsub/subscriptions', caller: __METHOD__,
            type: Subscriptions::class, options: $options, method: HttpMethods::get(), responseClass: TwitchEventSubGetSubscriptionsResponse::class, params: ['followPagination' => $followPagination, 'client' => $this, 'before' => $before,
                'after' => $after, 'filter' => $responseFilter]);
    }

    /**
     * @param IdInterface|string $id
     * @return ClientResponseInterface
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     */
    public function eventSubDelete($id): ClientResponseInterface
    {
        $id = IdNormalizer::normalizeIdArgument($id, 'The argument "id" is required.');
        return $this->request(url: 'https://api.twitch.tv/helix/eventsub/subscriptions', caller: __METHOD__, options: [
            'query' => [
                'id' => $id
            ]
        ], method: HttpMethods::delete(), onSuccessCallable: function ($self, $subscriptions) {
            /** @var TwitchResponse $self */
            if (array_key_exists('id', $self->getExtraParams())) {
                $id = $self->getExtraParams()['id'];
                $this->dispatchEventSubSubscriptionDeleteEvent($id);
            }
        }, params: ['id' => $id])->onSuccessCallback();
    }

    /**
     * @param string $eventId
     * @return EventSubSubscriptionDeleteEvent
     */
    protected function dispatchEventSubSubscriptionDeleteEvent(string $eventId): EventSubSubscriptionDeleteEvent
    {
        return $this->dispatch(EventSubSubscriptionDeleteEvent::make($eventId));
    }

    /**
     * @param EventSubSubscriptionGenerateCallbackEvent $eventSubSubscriptionGenerateCallbackEvent
     * @return $this
     */
    public function setEventSubSubscriptionGenerateCallbackEvent(EventSubSubscriptionGenerateCallbackEvent $eventSubSubscriptionGenerateCallbackEvent): self
    {
        $this->eventSubSubscriptionGenerateCallbackEvent = $eventSubSubscriptionGenerateCallbackEvent;
        return $this;
    }

    /**
     * @param EventSubSubscriptionTypes $type
     * @param UserInterface $stream
     * @return string
     *
     * @deprecated Since 0.0.2, use EventSubSubscriptionGenerateCallbackEvent instead
     */
    protected function generateEventSubSubscribeCallback(EventSubSubscriptionTypes $type, UserInterface $stream): string
    {
        trigger_deprecation('mrgoodbytes8667/twitch-client-bundle', '0.0.2', 'The "%s()" method has been deprecated.', __METHOD__);
        return $this->urlGenerator->generate($this->callbackName, [
            'type' => $type,
            'stream' => $stream->getUserId(),
            'login' => $stream->getLogin(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @param Auth|null $auth
     * @param bool $reset
     * @return AccessTokenInterface|null
     * @throws NoTokenException
     */
    protected function getToken(?Auth $auth = null, bool $reset = false): ?AccessTokenInterface
    {
        /** @var ObtainValidTokenEvent $event */
        $event = $this->dispatch(ObtainValidTokenEvent::new($this->getIdentifier(), $this->getTokenSource(), $this->getTokenUser()));
        if (!empty($event) && $event instanceof Event) {
            return $event?->getToken();
        }

        throw new NoTokenException();
    }
}