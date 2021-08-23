<?php


namespace Bytes\TwitchClientBundle\HttpClient\Token;


use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Event\TokenRevokedEvent;
use Bytes\ResponseBundle\Event\TokenValidatedEvent;
use Bytes\ResponseBundle\HttpClient\Token\AbstractTokenClient;
use Bytes\ResponseBundle\HttpClient\Token\TokenRevokeInterface;
use Bytes\ResponseBundle\HttpClient\Token\TokenValidateInterface;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Objects\Push;
use Bytes\ResponseBundle\Token\Exceptions\TokenRevokeException;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\ResponseBundle\Token\Interfaces\TokenValidationResponseInterface;
use Bytes\TwitchClientBundle\HttpClient\TwitchClientEndpoints;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Validate;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use UnexpectedValueException;
use function Symfony\Component\String\u;

/**
 * Class TwitchTokenClient
 * @package Bytes\TwitchClientBundle\HttpClient\Token
 */
abstract class AbstractTwitchTokenClient extends AbstractTokenClient implements TokenRevokeInterface, TokenValidateInterface
{
    /**
     * TwitchTokenClient constructor.
     * @param HttpClientInterface $httpClient
     * @param string|null $userAgent
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     */
    public function __construct(HttpClientInterface $httpClient, EventDispatcherInterface $dispatcher, string $clientId, string $clientSecret, ?string $userAgent, bool $revokeOnRefresh, bool $fireRevokeOnRefresh, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $headers = Push::createPush(value: $userAgent, key: 'User-Agent')->toArray();
        parent::__construct($httpClient, $dispatcher, $userAgent, $revokeOnRefresh, $fireRevokeOnRefresh, array_merge_recursive([
            // the options defined as values apply only to the URLs matching
            // the regular expressions defined as keys

            // Matches OAuth Token Get/Refresh API routes
            TwitchClientEndpoints::SCOPE_OAUTH_TOKEN => [
                'headers' => $headers,
                'body' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]
            ],

            // Matches OAuth Token Validation API routes
            TwitchClientEndpoints::SCOPE_OAUTH_VALIDATE => [
                'headers' => $headers,
            ],

            // Matches OAuth API routes (remaining)
            TwitchClientEndpoints::SCOPE_OAUTH => [
                'headers' => $headers,
                'query' => [
                    'client_id' => $clientId,
                ]
            ],
        ], $defaultOptionsByRegexp), $defaultRegexp);
    }

    /**
     * @return string|null
     */
    protected static function getTokenExchangeBaseUri()
    {
        return TwitchClientEndpoints::ENDPOINT_TWITCH_OAUTH;
    }

    /**
     * @return string|null
     */
    protected static function getTokenExchangeDeserializationClass()
    {
        return Token::class;
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
     * @param AccessTokenInterface $token
     * @return ClientResponseInterface
     *
     * @throws TransportExceptionInterface
     * @throws UnexpectedValueException
     */
    public function revokeToken(AccessTokenInterface $token): ClientResponseInterface
    {
        $tokenString = static::normalizeAccessToken($token, false, 'The $token argument is required and cannot be empty.');

        $response = $this->request($this->buildURL('oauth2/revoke'), options: ['query' => [
            'token' => $tokenString
        ]], method: HttpMethods::post(), onSuccessCallable: function ($self, $results) use ($token) {
            $this->dispatchTokenRevokedEvent($token);
        });
        if(!$response->isSuccess())
        {
            throw new TokenRevokeException($response->getResponse(), sprintf('Could not revoke token: %d', $response->getStatusCode()));
        }
        return $response->onSuccessCallback();
    }

    /**
     * Validates the provided access token
     * Should fire a TokenValidatedEvent on success if $fireCallback is true
     * @param AccessTokenInterface $token
     * @param bool $fireCallback Should a TokenValidatedEvent be fired?
     * @return TokenValidationResponseInterface|null
     */
    public function validateToken(AccessTokenInterface $token, bool $fireCallback = false): ?TokenValidationResponseInterface
    {
        $tokenString = static::normalizeAccessToken($token, false, 'The $token argument is required and cannot be empty.');

        return $this->request($this->buildURL('oauth2/validate'), type: Validate::class, options: [
            'headers' => [
                'Authorization' => 'OAuth ' . $tokenString
            ]
        ], method: HttpMethods::get(), onSuccessCallable: function ($self, $results) use ($fireCallback, $token) {
            if($fireCallback) {
                $this->dispatchTokenValidatedEvent($token, $results);
            }
        })->deserialize(false);
    }
}