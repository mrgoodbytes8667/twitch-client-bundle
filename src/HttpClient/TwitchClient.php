<?php


namespace Bytes\TwitchClientBundle\HttpClient;


use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Event\EventDispatcherTrait;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Interfaces\IdInterface;
use Bytes\ResponseBundle\Objects\IdNormalizer;
use Bytes\ResponseBundle\Objects\Push;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionCreatePostRequestEvent;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionCreatePreRequestEvent;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionDeleteEvent;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchFollowersResponse;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchUserResponse;
use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Condition;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Create;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Subscriptions;
use Bytes\TwitchResponseBundle\Objects\Follows\FollowersResponse;
use Bytes\TwitchResponseBundle\Objects\Interfaces\UserInterface;
use InvalidArgumentException;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Symfony\Component\String\u;

/**
 * Class TwitchClient
 * @package Bytes\TwitchClientBundle\HttpClient
 *
 */
class TwitchClient extends AbstractTwitchClient
{
    use EventDispatcherTrait;

    /**
     * TwitchClient constructor.
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
    public function __construct(HttpClientInterface $httpClient, ?RetryStrategyInterface $strategy, protected UrlGeneratorInterface $urlGenerator, string $clientId, string $clientSecret, protected string $hubSecret, ?string $userAgent, protected ?string $callbackName = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
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
        return $this->request(url: 'https://api.twitch.tv/helix/eventsub/subscriptions', type: Subscriptions::class, options: $params, method: HttpMethods::post(), onSuccessCallable: function ($self, $subscriptions) {
            /** @var TwitchResponse $self */
            if (array_key_exists('user', $self->getExtraParams())) {
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
        ], method: HttpMethods::delete(), onSuccessCallable: function ($self, $subscriptions) {
            /** @var TwitchResponse $self */
            if (array_key_exists('id', $self->getExtraParams())) {
                $user = $self->getExtraParams()['id'];
                $this->dispatcher->dispatch(EventSubSubscriptionDeleteEvent::make($user));
            }
        }, params: ['id' => $id]);
    }

    /**
     * Get Users
     * Gets information about one or more specified Twitch users. Users are identified by optional user IDs and/or login
     * name. If neither a user ID nor a login name is specified, the user is looked up by Bearer token.
     * The limit of 100 IDs and login names is the total limit. You can request, for example, 50 of each or 100 of one
     * of them. You cannot request 100 of both. (This function will drop all remaining params over 100 if $throw is
     * false, otherwise it throws an exception).
     * @param string[] $ids
     * @param string[] $logins
     * @param bool $throw
     * @return ClientResponseInterface
     * @throws TransportExceptionInterface
     * @link https://dev.twitch.tv/docs/api/reference#get-users
     */
    public function getUsers(array $ids = [], array $logins = [], bool $throw = true, ClientResponseInterface|string|null $responseClass = null): ClientResponseInterface
    {
        if ($throw) {
            if (count($ids) + count($logins) > 100) {
                throw new InvalidArgumentException('There can only be a maximum of 100 combined ids and logins.');
            }
        }
        $url = u('https://api.twitch.tv/helix/users?');
        $counter = 0;
        foreach ($ids as $id) {
            if ($counter < 100) {
                $id = IdNormalizer::normalizeIdArgument($id, 'The "id" argument is required.');
                $url = $url->append('id=' . $id . '&');
                $counter++;
            } else {
                break;
            }
        }
        if (!empty($url) && !empty($logins) && $counter < 100) {
            $url = $url->append('&');
        }
        foreach ($logins as $login) {
            if ($counter < 100) {
                $url = $url->append('login=' . $login . '&');
                $counter++;
            } else {
                break;
            }
        }
        $url = $url->beforeLast('&')->toString();
        return $this->request(url: $url,
            type: '\Bytes\TwitchResponseBundle\Objects\Users\User[]',
            responseClass: $responseClass, context: [UnwrappingDenormalizer::UNWRAP_PATH => '[data]']);
    }

    /**
     * Helper wrapper around getUsers that will deserialize into a single User
     * @param string|null $id
     * @param string|null $login
     * @return ClientResponseInterface
     * @throws TransportExceptionInterface
     */
    public function getUser(?string $id = null, ?string $login = null)
    {
        return $this->getUsers(ids: [$id], logins: [$login], throw: false, responseClass: TwitchUserResponse::class);
    }

    /**
     * Get Users Follows
     * Gets information on follow relationships between two Twitch users. This can return information like "who is
     * qotrok following," "who is following qotrok", or "is user X following user Y." Information returned is sorted in
     * order, most recent follow first.
     *
     * If both $fromId and $toId are provided, $toId will be ignored.
     * @param string|null $fromId
     * @param string|null $toId
     * @param string|null $after
     * @param int $limit
     * @param bool $followPagination
     * @return ClientResponseInterface
     * @throws TransportExceptionInterface
     *
     * @link https://dev.twitch.tv/docs/api/reference#get-users-follows
     */
    public function getFollows(?string $fromId = null, ?string $toId = null, ?string $after = null, int $limit = 100, bool $followPagination = false): ClientResponseInterface
    {

        if (empty($fromId) && empty($toId)) {
            throw new InvalidArgumentException('Either "fromId" or "toId" must be provided.');
        }

        $query = Push::createPush(value: $fromId, key: 'from_id')
            ->push($after, 'after')
            ->push($limit, 'first');

        if (empty($fromId)) {
            $query = $query->push($toId, 'to_id');
        }

        $query = $query->value();

        return $this->request(url: 'https://api.twitch.tv/helix/users/follows', type: FollowersResponse::class,
            options: ['query' => $query], responseClass: TwitchFollowersResponse::class,
            params: ['followPagination' => $followPagination, 'client' => $this, 'fromId' => $fromId,
                'toId' => $toId, 'limit' => $limit]);
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