<?php


namespace Bytes\TwitchClientBundle\HttpClient;


use Bytes\ResponseBundle\Enums\HttpMethods;
use Bytes\ResponseBundle\Event\EventDispatcherTrait;
use Bytes\ResponseBundle\Event\TokenRevokedEvent;
use Bytes\ResponseBundle\HttpClient\AbstractTokenClient;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Objects\Push;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\ResponseBundle\Token\Interfaces\TokenRevokeInterface;
use Bytes\ResponseBundle\Token\Interfaces\TokenValidateInterface;
use Bytes\ResponseBundle\Token\Interfaces\TokenValidationResponseInterface;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Validate;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Symfony\Component\String\u;

/**
 * Class TwitchTokenClient
 * @package Bytes\TwitchClientBundle\HttpClient
 */
abstract class AbstractTwitchTokenClient extends AbstractTokenClient implements TokenRevokeInterface, TokenValidateInterface
{
    use EventDispatcherTrait;

    /**
     * TwitchTokenClient constructor.
     * @param HttpClientInterface $httpClient
     * @param string|null $userAgent
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     */
    public function __construct(HttpClientInterface $httpClient, string $clientId, string $clientSecret, ?string $userAgent, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $headers = Push::createPush(value: $userAgent, key: 'User-Agent')->value();
        parent::__construct($httpClient, $userAgent, array_merge_recursive([
            // the options defined as values apply only to the URLs matching
            // the regular expressions defined as keys

            // Matches OAuth Token Get/Refresh API routes
            TwitchClientEndpoints::SCOPE_OAUTH_TOKEN => [
                'headers' => $headers,
                'query' => [
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
        ]], method: HttpMethods::post(), onSuccessCallable: function ($self, $results) {
            /** @var ClientResponseInterface $self */
            if (array_key_exists('token', $self->getExtraParams())) {
                $token = $self->getExtraParams()['token'];
                if(!empty($token) && is_string($token)) {
                    $token = Token::createFromAccessToken($token);
                }
                if(!empty($token) && $token instanceof AccessTokenInterface) {
                    $this->dispatcher->dispatch(TokenRevokedEvent::new($token), TokenRevokedEvent::NAME);
                }
            }
        }, params: ['token' => Token::createFromAccessToken($token)]);
    }

    /**
     * Validates the provided access token
     * @param AccessTokenInterface|string $token
     * @return TokenValidationResponseInterface|null
     */
    public function validateToken(AccessTokenInterface|string $token): ?TokenValidationResponseInterface
    {
        $token = static::normalizeAccessToken($token, false, 'The $token argument is required and cannot be empty.');

        try {
            return $this->request($this->buildURL('oauth2/validate'), type: Validate::class, options: [
                'headers' => [
                    'Authorization' => 'OAuth ' . $token
                ]
            ], method: HttpMethods::get())->deserialize(false);
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $exception) {
            return null;
        }
    }
}