<?php


namespace Bytes\TwitchClientBundle\HttpClient;


use Bytes\TwitchResponseBundle\Enums\OAuthScopes;
use Bytes\TwitchResponseBundle\Objects\Embed\Embed;
use Bytes\TwitchResponseBundle\Objects\Interfaces\IdInterface;
use Bytes\TwitchResponseBundle\Objects\Message;
use Bytes\TwitchResponseBundle\Objects\Message\AllowedMentions;
use Bytes\TwitchResponseBundle\Objects\Message\WebhookContent;
use Bytes\TwitchResponseBundle\Objects\Token;
use Bytes\TwitchResponseBundle\Objects\User;
use Bytes\TwitchResponseBundle\Services\IdNormalizer;
use Bytes\HttpClient\Common\HttpClient\ConfigurableScopingHttpClient;
use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Enums\OAuthGrantTypes;
use InvalidArgumentException;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Symfony\Component\String\u;

/**
 * Class TwitchClient
 * @package Bytes\TwitchClientBundle\HttpClient
 */
class TwitchClient implements SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     *
     */
    const PREVENTATIVE_RATE_LIMIT_SECONDS = 2;

    /**
     * Matches Slash Command API routes
     */
    const SCOPE_SLASH_COMMAND = 'https://twitch\.com/api/v8/applications';

    /**
     * Matches OAuth token revoke API routes
     */
    const SCOPE_OAUTH_TOKEN_REVOKE = 'https://twitch\.com/api(|/v6|/v8)/oauth2/token/revoke';

    /**
     * Matches OAuth token API routes
     */
    const SCOPE_OAUTH_TOKEN = 'https://twitch\.com/api(|/v6|/v8)/oauth2/token';

    /**
     * Matches OAuth API routes (though there shouldn't be any...)
     */
    const SCOPE_OAUTH = 'https://twitch\.com/api(|/v6|/v8)/oauth2';

    /**
     * Matches non-oauth API routes
     */
    const SCOPE_API = 'https://twitch\.com/api(|/v6|/v8)/((?!oauth2).)';

    const ENDPOINT_CHANNEL = 'channels';
    const ENDPOINT_GUILD = 'guilds';
    const ENDPOINT_MESSAGE = 'messages';
    const ENDPOINT_MEMBER = 'members';
    const ENDPOINT_USER = 'users';
    const USER_ME = '@me';
    const ENDPOINT_WEBHOOK = 'webhooks';

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var TwitchResponse
     */
    protected $response;

    /**
     * TwitchClient constructor.
     * @param HttpClientInterface $httpClient
     * @param RetryStrategyInterface|null $strategy
     * @param string $clientId
     * @param string $clientSecret
     * @param string $botToken
     * @param string|null $userAgent
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     */
    public function __construct(protected HttpClientInterface $httpClient, ?RetryStrategyInterface $strategy, protected string $clientId, string $clientSecret, string $botToken, ?string $userAgent, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $headers = [];
        if (!empty($userAgent)) {
            $headers['User-Agent'] = $userAgent;
        }
        $this->httpClient = new RetryableHttpClient(new ConfigurableScopingHttpClient($httpClient, array_merge_recursive([
            // the options defined as values apply only to the URLs matching
            // the regular expressions defined as keys

            // Matches Slash Command API routes
            self::SCOPE_SLASH_COMMAND => [
                'headers' => array_merge($headers, [
                    'Authorization' => 'Bot ' . $botToken,
                ]),
            ],

            // Matches OAuth token revoke API routes
            self::SCOPE_OAUTH_TOKEN_REVOKE => [
                'headers' => $headers,
                'query' => [
                    'client_id' => $clientId,
                ]
            ],
            // Matches OAuth token API routes
            self::SCOPE_OAUTH_TOKEN => [
                'headers' => $headers,
                'body' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]
            ],
            // Matches OAuth API routes (though there shouldn't be any...)
            self::SCOPE_OAUTH => [
                'headers' => $headers,
            ],

            // Matches non-oauth API routes
            self::SCOPE_API => [
                'headers' => $headers,
            ],
        ], $defaultOptionsByRegexp), ['query', 'body'], $defaultRegexp), $strategy);
        $this->clientId = $clientId;
    }

    /**
     * @param string|string[] $url
     * @param string|null $type
     * @param array $options = HttpClientInterface::OPTIONS_DEFAULTS
     * @param string $method = ['GET','HEAD','POST','PUT','DELETE','CONNECT','OPTIONS','TRACE','PATCH'][$any]
     * @return TwitchResponse
     * @throws TransportExceptionInterface
     */
    public function request($url, ?string $type = null, array $options = [], $method = 'GET')
    {
        if (is_array($url)) {
            $url = implode('/', $url);
        }
        if (empty($url) || !is_string($url)) {
            throw new InvalidArgumentException();
        }
        $auth = $this->getAuthenticationOption();
        if (!empty($auth) && is_array($auth)) {
            $options = array_merge_recursive($options, $auth);
        }
        return $this->response->withResponse($this->httpClient->request($method, $this->buildURL($url), $options), $type);
    }

    /**
     * @return array
     */
    protected function getAuthenticationOption()
    {
        return [];
    }

    /**
     * @param string $path
     * @param string $version
     * @return string
     */
    protected function buildURL(string $path, string $version = 'v8')
    {
        $url = u($path);
        if ($url->startsWith('https://twitch.com/api/')) {
            return $path;
        }
        if (!empty($version)) {
            $url = $url->ensureStart($version . '/');
        }
        return $url->ensureStart('https://twitch.com/api/')->toString();
    }

    /**
     * @param string $code
     * @param string $redirect
     * @param array $scopes
     * @param OAuthGrantTypes|null $grantType
     * @return Token|null
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function tokenExchange(string $code, string $redirect, array $scopes = [], OAuthGrantTypes $grantType = null)
    {
        if (empty($scopes)) {
            $scopes = OAuthScopes::getBotScopes();
        }
        if (empty($grantType)) {
            $grantType = OAuthGrantTypes::authorizationCode();
        }
        $body = [
            'grant_type' => $grantType->value,
            'redirect_uri' => $redirect,
            'scope' => OAuthScopes::buildOAuthString($scopes),
        ];
        switch ($grantType) {
            case OAuthGrantTypes::authorizationCode():
                $body['code'] = $code;
                break;
            case OAuthGrantTypes::refreshToken():
                $body['refresh_token'] = $code;
                break;
        }

        return $this->request($this->buildURL('oauth2/token', ''),
            Token::class,
            [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => $body,
            ], HttpMethods::post())
            ->deserialize();
    }

    /**
     * @param ValidatorInterface $validator
     * @return $this
     */
    public function setValidator(ValidatorInterface $validator): self
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * @param TwitchResponse $response
     * @return $this
     */
    public function setResponse(TwitchResponse $response): self
    {
        $this->response = $response;
        return $this;
    }
}