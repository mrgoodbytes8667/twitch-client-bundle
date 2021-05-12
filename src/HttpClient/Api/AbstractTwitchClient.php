<?php


namespace Bytes\TwitchClientBundle\HttpClient\Api;


use Bytes\ResponseBundle\Annotations\Client;
use Bytes\ResponseBundle\HttpClient\Api\AbstractApiClient;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Objects\Push;
use Bytes\ResponseBundle\Validator\ValidatorTrait;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchClientBundle\HttpClient\TwitchClientEndpoints;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class AbstractTwitchClient
 * @package Bytes\TwitchClientBundle\HttpClient\Api
 *
 * @var TwitchResponse|ClientResponseInterface $response
 *
 * https://id.twitch.tv/oauth2/validate - Validate token (curl -H "Authorization: OAuth <access token>" https://id.twitch.tv/oauth2/validate)
 * https://id.twitch.tv/oauth2/revoke?client_id=uo6dggojyb8d6soh92zknwmi5ej1q2&token=0123456789abcdefghijABCDEFGHIJ - Revoke token
 * https://id.twitch.tv/oauth2/token - refresh (client id/secret in body), get app/user token (client id/secret in query string)
 * https://id.twitch.tv/oauth2/authorize - send to get token, not applicable for HttpClient
 *
 * https://api.twitch.tv/helix - all helix calls
 * https://api.twitch.tv/kraken - all kraken calls
 *
 * https://api.twitch.tv/helix/eventsub/subscriptions - create sub, headers: Client-ID, Authorization: Bearer token
 */
abstract class AbstractTwitchClient extends AbstractApiClient implements SerializerAwareInterface
{
    use SerializerAwareTrait, ValidatorTrait;

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
    public function __construct(HttpClientInterface $httpClient, ?RetryStrategyInterface $strategy, string $clientId, string $clientSecret, ?string $userAgent, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $headers = Push::createPush(value: $userAgent, key: 'User-Agent')->value();
        parent::__construct($httpClient, $strategy, $clientId, $userAgent, array_merge_recursive([
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
}