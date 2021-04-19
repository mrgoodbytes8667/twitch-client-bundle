<?php


namespace Bytes\TwitchClientBundle\HttpClient;


use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Interfaces\IdInterface;
use Bytes\ResponseBundle\Objects\IdNormalizer;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionCreatePostRequestEvent;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionCreatePreRequestEvent;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionDeleteEvent;
use Bytes\TwitchClientBundle\HttpClient\Response\EventSubDeleteResponse;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Condition;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Create;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Subscriptions;
use Bytes\TwitchResponseBundle\Objects\Interfaces\UserInterface;
use InvalidArgumentException;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class TwitchClient
 * @package Bytes\TwitchClientBundle\HttpClient
 *
 */
class TwitchClient extends AbstractTwitchClient
{
    public function __construct(HttpClientInterface $httpClient, ?RetryStrategyInterface $strategy, protected EventDispatcherInterface $dispatcher, protected UrlGeneratorInterface $urlGenerator, string $clientId, string $clientSecret, protected string $hubSecret, ?string $userAgent, protected ?string $callbackName = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        parent::__construct($httpClient, $strategy, $clientId, $clientSecret, $userAgent, $defaultOptionsByRegexp, $defaultRegexp);
    }

    /**
     * @param EventSubSubscriptionTypes $type
     * @param UserInterface $stream
     * @param null $callback
     * @param array $extraConditions
     * @return ClientResponseInterface
     * @throws TransportExceptionInterface
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
                $conditions->setBroadcasterUserId($stream->getId());
                break;
            case EventSubSubscriptionTypes::userUpdate():
                $conditions->setUserId($stream->getId());
                break;
            default:
                throw new InvalidArgumentException(sprintf("The type '%s' is not yet supported", $type));
                break;
        }

        if (is_string($callback)) {
            $url = $callback;
        } elseif (is_null($callback)) {
            $url = call_user_func([$this, 'generateEventSubSubscribeCallback'], $type, $stream);
        } elseif (is_callable($callback)) {
            $url = call_user_func($callback, $type, $stream);
        } else {
            throw new InvalidArgumentException('The argument "callback" must be a valid URL, a callback, or null.');
        }

        $create = Create::create($type, $conditions, $url, $this->hubSecret);
        $json = $this->serializer->serialize($create, 'json', [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]);
        $params['body'] = $json;

        $this->dispatcher?->dispatch(EventSubSubscriptionCreatePreRequestEvent::make($type, $stream, $url));
        return $this->request(url: 'https://api.twitch.tv/helix/eventsub/subscriptions', type: Subscriptions::class, options: $params, method: HttpMethods::post(), onSuccessCallable: function($self, $subscriptions) {
            /** @var TwitchResponse $self */
            if(array_key_exists('user', $self->getExtraParams()))
            {
                $user = $self->getExtraParams()['user'];
            }
            $this->dispatcher->dispatch(EventSubSubscriptionCreatePostRequestEvent::createFromSubscription($subscriptions->getSubscription(), $user));
        }, params: ['user' => $stream]);
    }

    /**
     * @param IdInterface|string $id
     * @return ClientResponseInterface
     * @throws TransportExceptionInterface
     */
    public function eventSubDelete($id): ClientResponseInterface
    {
        $id = IdNormalizer::normalizeIdArgument($id, 'The argument "id" is required.');
        return $this->request(url: 'https://api.twitch.tv/helix/eventsub/subscriptions', options: [
            'query' => [
                'id' => $id
            ]
        ], method: HttpMethods::delete(), onSuccessCallable: function($self, $subscriptions) {
            /** @var TwitchResponse $self */
            if(array_key_exists('id', $self->getExtraParams()))
            {
                $user = $self->getExtraParams()['id'];
                $this->dispatcher->dispatch(EventSubSubscriptionDeleteEvent::make($user));
            }
        }, params: ['id' => $id]);
    }

    /**
     * @param EventSubSubscriptionTypes $type
     * @param UserInterface $stream
     * @return string
     */
    protected function generateEventSubSubscribeCallback(EventSubSubscriptionTypes $type, UserInterface $stream): string
    {
        return $this->urlGenerator->generate($this->callbackName, [
            'type' => $type,
            'stream' => $stream->getId(),
            'login' => $stream->getLogin(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}