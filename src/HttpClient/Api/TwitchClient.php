<?php


namespace Bytes\TwitchClientBundle\HttpClient\Api;


use Bytes\ResponseBundle\Annotations\Client;
use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Objects\IdNormalizer;
use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\TwitchResponseBundle\Objects\Streams\StreamsResponse;
use InvalidArgumentException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use function Symfony\Component\String\u;

/**
 * Class TwitchClient
 * @package Bytes\TwitchClientBundle\HttpClient\Api
 *
 * @Client(identifier="TWITCH", tokenSource="app")
 */
class TwitchClient extends AbstractTwitchClient
{
    /**
     * Return the client name
     * @return string
     */
    public static function getDefaultIndexName(): string
    {
        return 'TWITCH';
    }


    /**
     * Get Streams
     * Gets information about active streams. Streams are returned sorted by number of current viewers, in descending
     * order. Across multiple pages of results, there may be duplicate or missing streams, as viewers join and leave
     * streams.
     * @param array $gameIds
     * @param array $userIds
     * @param array $logins
     * @param int $first
     * @param bool $throw
     * @return ClientResponseInterface
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     * @link https://dev.twitch.tv/docs/api/reference#get-streams
     *
     * @todo Pagination
     */
    public function getStreams(array $gameIds = [], array $userIds = [], array $logins = [], int $first = 20, bool $throw = true): ClientResponseInterface
    {
        if ($throw) {
            if (count($userIds) + count($logins) > 100) {
                throw new InvalidArgumentException('There can only be a maximum of 100 combined user ids and logins.');
            }
            if (count($gameIds) > 100) {
                throw new InvalidArgumentException('There can only be a maximum of 100 game ids.');
            }
        }
        $url = u('https://api.twitch.tv/helix/streams?');
        $counter = 0;
        foreach ($userIds as $id) {
            if ($counter < 100) {
                $id = IdNormalizer::normalizeIdArgument($id, 'The "userIds" argument is required.');
                $url = $url->append('user_id=' . $id . '&');
                $counter++;
            } else {
                break;
            }
        }
        if (!empty($logins) && $counter < 100 && !$url->endsWith('?')) {
            $url = $url->ensureEnd('&');
        }
        foreach ($logins as $login) {
            if ($counter < 100) {
                $url = $url->append('user_login=' . $login . '&');
                $counter++;
            } else {
                break;
            }
        }
        $counter = 0;
        if (!empty($gameIds) && !$url->endsWith('?')) {
            $url = $url->ensureEnd('&');
        }
        foreach ($gameIds as $id) {
            if ($counter < 100) {
                $id = IdNormalizer::normalizeIdArgument($id, 'The "gameIds" argument is required.');
                $url = $url->append('game_id=' . $id . '&');
                $counter++;
            } else {
                break;
            }
        }
        $url = $url->append('first=')->append($first)->toString();
        return $this->request(url: $url, caller: __METHOD__,
            type: StreamsResponse::class, method: HttpMethods::get());
    }
}