<?php


namespace Bytes\TwitchClientBundle\HttpClient\Api;


use Bytes\ResponseBundle\HttpClient\Api\AbstractApiClient;
use Bytes\ResponseBundle\HttpClient\ApiAuthenticationTrait;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Objects\IdNormalizer;
use Bytes\ResponseBundle\Objects\Push;
use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\ResponseBundle\Validator\ValidatorTrait;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchFollowersResponse;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchTopGamesResponse;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchUserResponse;
use Bytes\TwitchClientBundle\HttpClient\TwitchClientEndpoints;
use Bytes\TwitchResponseBundle\Objects\Follows\FollowersResponse;
use Bytes\TwitchResponseBundle\Objects\Games\GamesResponse;
use InvalidArgumentException;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Symfony\Component\String\u;

/**
 * Class AbstractTwitchClient
 * @package Bytes\TwitchClientBundle\HttpClient\Api
 *
 * @var TwitchResponse|ClientResponseInterface $response
 */
abstract class AbstractTwitchClient extends AbstractApiClient implements SerializerAwareInterface
{
    use ApiAuthenticationTrait, SerializerAwareTrait, ValidatorTrait;

    /**
     * TwitchClient constructor.
     * @param HttpClientInterface $httpClient
     * @param RetryStrategyInterface|null $strategy
     * @param string $clientId
     * @param string $clientSecret
     * @param string|null $userAgent
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     */
    public function __construct(HttpClientInterface $httpClient, EventDispatcherInterface $dispatcher, ?RetryStrategyInterface $strategy, string $clientId, string $clientSecret, ?string $userAgent, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $headers = Push::createPush(value: $userAgent, key: 'User-Agent')->value();
        parent::__construct($httpClient, $dispatcher, $strategy, $clientId, $userAgent, array_merge_recursive([
            // the options defined as values apply only to the URLs matching
            // the regular expressions defined as keys

            // Matches Kraken API routes - Authorization: OAuth <token>
            TwitchClientEndpoints::SCOPE_KRAKEN => [
                'headers' => array_merge($headers, [
                    'Client-Id' => $clientId,
                ]),
            ],

            // Matches Helix API routes - auth_bearer
            TwitchClientEndpoints::SCOPE_HELIX => [
                'headers' => array_merge($headers, [
                    'Client-Id' => $clientId,
                ]),
            ],

            // Matches OAuth token revoke API routes
            TwitchClientEndpoints::SCOPE_OAUTH_TOKEN_REVOKE => [
                'headers' => $headers,
                'query' => [
                    'client_id' => $clientId,
                ]
            ],
        ], $defaultOptionsByRegexp), $defaultRegexp);
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
        return $this->getUsers(ids: !is_null($id) ? [$id] : [], logins: !is_null($login) ? [$login] : [], throw: false, responseClass: TwitchUserResponse::class, caller: __METHOD__);
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
    public function getUsers(array $ids = [], array $logins = [], bool $throw = true, ClientResponseInterface|string|null $responseClass = null, ReflectionMethod|string $caller = __METHOD__): ClientResponseInterface
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
        return $this->request(url: $url, caller: $caller,
            type: '\Bytes\TwitchResponseBundle\Objects\Users\User[]',
            responseClass: $responseClass, context: [UnwrappingDenormalizer::UNWRAP_PATH => '[data]']);
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

        return $this->request(url: 'https://api.twitch.tv/helix/users/follows', caller: __METHOD__, type: FollowersResponse::class,
            options: ['query' => $query], responseClass: TwitchFollowersResponse::class,
            params: ['followPagination' => $followPagination, 'client' => $this, 'fromId' => $fromId,
                'toId' => $toId, 'limit' => $limit]);
    }

    /**
     * Get Top Games
     * Gets games sorted by number of current viewers on Twitch, most popular first.
     * @param int $limit
     * @param bool $throw
     * @param string|null $before
     * @param string|null $after
     * @param bool $followPagination
     * @return ClientResponseInterface
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     *
     * @link https://dev.twitch.tv/docs/api/reference#get-top-games
     */
    public function getTopGames(int $limit = 20, bool $throw = true, ?string $before = null, ?string $after = null, bool $followPagination = true): ClientResponseInterface
    {
        if ($limit < 1) {
            if ($throw) {
                throw new InvalidArgumentException('The "limit" argument must be greater than or equal to 1.');
            }
            $limit = 1;
        }
        $handoffLimit = $limit;
        if ($limit > 100) {
            $limit = 100;
        }

        $query = Push::createPush(value: $limit, key: 'first')
            ->push($before, 'before')
            ->push($after, 'after');

        return $this->request(url: 'https://api.twitch.tv/helix/games/top', caller: __METHOD__, type: GamesResponse::class,
            options: ['query' => $query->value()], responseClass: TwitchTopGamesResponse::class,
            params: ['followPagination' => $followPagination, 'client' => $this, 'before' => $before,
                'after' => $after, 'limit' => $handoffLimit - $limit]);
    }

    /**
     * Helper wrapper around getGames that will deserialize into a single Game
     * @param string|null $id
     * @param string|null $name
     * @return ClientResponseInterface
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     */
    public function getGame(?string $id = null, ?string $name = null)
    {
        return $this->getGames(ids: !is_null($id) ? [$id] : [], names: !is_null($name) ? [$name] : [], throw: false, responseClass: TwitchUserResponse::class, caller: __METHOD__);
    }

    /**
     * Get Games
     * Gets game information by game ID or name.
     * @param string[] $ids
     * @param string[] $names
     * @param bool $throw
     * @param ClientResponseInterface|string|null $responseClass
     * @param ReflectionMethod|string $caller
     * @return ClientResponseInterface
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     */
    public function getGames(array $ids = [], array $names = [], bool $throw = true, ClientResponseInterface|string|null $responseClass = null, ReflectionMethod|string $caller = __METHOD__)
    {
        if ($throw) {
            if (count($ids) + count($names) > 100) {
                throw new InvalidArgumentException('There can only be a maximum of 100 combined ids and names.');
            }
        }
        $url = u('https://api.twitch.tv/helix/games?');
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
        if (!empty($url) && !empty($names) && $counter < 100) {
            $url = $url->append('&');
        }
        foreach ($names as $name) {
            if ($counter < 100) {
                $url = $url->append('name=' . $name . '&');
                $counter++;
            } else {
                break;
            }
        }
        $url = $url->beforeLast('&')->toString();
        return $this->request(url: $url, caller: $caller,
            type: '\Bytes\TwitchResponseBundle\Objects\Games\Game[]',
            responseClass: $responseClass, context: [UnwrappingDenormalizer::UNWRAP_PATH => '[data]']);
    }

    /**
     * @param string $path
     * @param string $prepend
     * @return string
     */
    protected function buildURL(string $path, string $prepend = 'helix')
    {
        $url = u($path);
        if ($url->startsWith(TwitchClientEndpoints::ENDPOINT_TWITCH_API)) {
            return $path;
        }
        if (!empty($prepend)) {
            $url = $url->ensureStart($prepend . '/');
        }
        return $url->ensureStart(TwitchClientEndpoints::ENDPOINT_TWITCH_API)->toString();
    }
}