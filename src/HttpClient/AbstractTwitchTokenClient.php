<?php


namespace Bytes\TwitchClientBundle\HttpClient;


use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\HttpClient\AbstractTokenClient;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Objects\Push;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\ResponseBundle\Token\Interfaces\TokenRevokeInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Symfony\Component\String\u;

/**
 * Class TwitchTokenClient
 * @package Bytes\TwitchClientBundle\HttpClient
 */
abstract class AbstractTwitchTokenClient extends AbstractTokenClient implements TokenRevokeInterface
{
    /**
     * TwitchTokenClient constructor.
     * @param HttpClientInterface $httpClient
     * @param string|null $userAgent
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     */
    public function __construct(HttpClientInterface $httpClient, string $clientId, string $clientSecret, ?string $userAgent, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $headers = Push::createPush(value($userAgent, 'User-Agent'))->value();
        parent::__construct($httpClient, $userAgent, array_merge_recursive([
            // the options defined as values apply only to the URLs matching
            // the regular expressions defined as keys

            // Matches OAuth API routes
            TwitchClientEndpoints::SCOPE_OAUTH_TOKEN => [
                'headers' => $headers,
                'query' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]
            ],

//            // Matches OAuth API routes
//            TwitchClientEndpoints::SCOPE_OAUTH_VALIDATE => [
//                'headers' => $headers,
//            ],

            // Matches OAuth API routes
            TwitchClientEndpoints::SCOPE_OAUTH => [
                'headers' => $headers,
                'query' => [
                    'client_id' => $clientId,
                ]
            ],
        ], $defaultOptionsByRegexp), $defaultRegexp);
    }

    /**
     * @param string $path
     * @param string $prepend
     * @return string
     */
    protected function buildURL(string $path, string $prepend = '')
    {
//        $url = u($path);
//        if ($url->startsWith(TwitchClientEndpoints::ENDPOINT_TWITCH_OAUTH)) {
//            return $path;
//        }
//        if (!empty($prepend)) {
//            $url = $url->ensureStart($prepend . '/');
//        }
        return u($path)->ensureStart(TwitchClientEndpoints::ENDPOINT_TWITCH_OAUTH)->toString();
    }

    /**
     * Revokes the provided access token
     * @param AccessTokenInterface|string $token
     * @return ClientResponseInterface
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function revokeToken(AccessTokenInterface|string $token): ClientResponseInterface
    {
        $token = static::normalizeAccessToken($token, false, 'The $token argument is required and cannot be empty.');

        return $this->request($this->buildURL('oauth2/revoke'), options: ['query' => [
            'token' => $token
        ]], method: HttpMethods::post());
    }
}