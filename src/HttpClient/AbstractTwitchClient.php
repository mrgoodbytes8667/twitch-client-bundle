<?php


namespace Bytes\TwitchClientBundle\HttpClient;


use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Enums\OAuthGrantTypes;
use Bytes\ResponseBundle\HttpClient\AbstractApiClient;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Validator\ValidatorTrait;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchResponseBundle\Enums\OAuthScopes;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class AbstractTwitchClient
 * @package Bytes\TwitchClientBundle\HttpClient
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
     * Matches OAuth API token revoke route
     */
    const SCOPE_OAUTH_TOKEN_REVOKE = 'https://id\.twitch\.tv/oauth2/revoke';

    /**
     * Matches remaining OAuth API routes
     */
    const SCOPE_OAUTH = 'https://id\.twitch\.tv/oauth2';

    /**
     * Matches Kraken API routes
     */
    const SCOPE_KRAKEN = 'https://api\.twitch\.tv/kraken';

    /**
     * Matches Helix API routes
     */
    const SCOPE_HELIX = 'https://api\.twitch\.tv/helix';

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
        $headers = [];
        if (!empty($userAgent)) {
            $headers['User-Agent'] = $userAgent;
        }
        parent::__construct($httpClient, $strategy, $clientId, $userAgent, array_merge_recursive([
            // the options defined as values apply only to the URLs matching
            // the regular expressions defined as keys

            // Matches Kraken API routes - Authorization: OAuth <token>
            self::SCOPE_KRAKEN => [
                'headers' => array_merge($headers, [
                    'Client-Id' => $clientId,
                ]),
            ],

            // Matches Helix API routes - auth_bearer
            self::SCOPE_HELIX => [
                'headers' => array_merge($headers, [
                    'Client-Id' => $clientId,
                ]),
            ],

            // Matches OAuth token revoke API routes
            self::SCOPE_OAUTH_TOKEN_REVOKE => [
                'headers' => $headers,
                'query' => [
                    'client_id' => $clientId,
                ]
            ],

            // Matches OAuth API routes
            self::SCOPE_OAUTH => [
                'headers' => $headers,
            ],
        ], $defaultOptionsByRegexp), $defaultRegexp);
    }
}