<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchTokenClient;

use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Enums\TokenSource;
use Bytes\ResponseBundle\Exception\Response\EmptyContentException;
use Bytes\ResponseBundle\Interfaces\ClientTokenResponseInterface;
use Bytes\ResponseBundle\Test\AssertAccessTokenInterfaceTrait;
use Bytes\ResponseBundle\Test\AssertClientAnnotationsSameTrait;
use Bytes\ResponseBundle\Token\Exceptions\TokenRevokeException;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\ResponseBundle\Token\Interfaces\TokenValidationResponseInterface;
use Bytes\Tests\Common\MockHttpClient\MockEmptyResponse;
use Bytes\TwitchClientBundle\Routing\TwitchUserOAuth;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestHttpClientCase;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestTwitchClientTrait;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchClientBundle\Tests\TestUrlGeneratorTrait;
use Bytes\TwitchClientBundle\Tests\TwitchClientSetupTrait;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
use Exception;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class TwitchUserTokenClientTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchTokenClient
 */
class TwitchUserTokenClientTest extends TestHttpClientCase
{
    use AssertAccessTokenInterfaceTrait, AssertClientAnnotationsSameTrait, TestTwitchClientTrait, TestTwitchFakerTrait, TestUrlGeneratorTrait, TwitchClientSetupTrait {
        TwitchClientSetupTrait::setupUserTokenClient as setupClient;
    }

    /**
     * @throws Exception
     */
    public function testRefreshToken()
    {
        $redirect = 'https://www.example.com';
        $oAuth = new TwitchUserOAuth($this->faker->id(), [
            'app' => [
                'redirects' => [
                    'method' => 'url',
                    'url' => $redirect,
                ]
            ],
            'login' => [
                'redirects' => [
                    'method' => 'url',
                    'url' => $redirect,
                ]
            ],
            'user' => [
                'redirects' => [
                    'method' => 'url',
                    'url' => $redirect,
                ]
            ],
        ]);
        $oAuth->setValidator($this->validator);

        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/get-app-token-success.json')
        ]))
            ->setOAuth($oAuth);

        $existingToken = Token::createFromAccessToken($this->faker->accessToken());
        $existingToken->setRefreshToken($this->faker->refreshToken());

        $token = $client->refreshToken($existingToken);
        $this->assertInstanceOf(AccessTokenInterface::class, $token);
        $this->assertEquals('vkhp8pva30rb0df4z80fesvarzpxn8', $token->getAccessToken());

        // Re-test with a null refresh token
        $this->assertNull($client->refreshToken(Token::createFromAccessToken($this->faker->accessToken())));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testRevokeToken()
    {
        $client = $this->setupClient(MockClient::requests(new MockEmptyResponse(Response::HTTP_OK)));

        $response = $client->revokeToken(Token::createFromAccessToken($this->faker->accessToken()));
        $this->assertInstanceOf(ClientTokenResponseInterface::class, $response);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasNoContent($response);

        $response->onSuccessCallback();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testRevokeTokenInvalidToken()
    {
        $client = $this->setupClient(MockClient::requests(MockJsonResponse::make(['status' => Response::HTTP_BAD_REQUEST, 'message' => 'Invalid token'], Response::HTTP_BAD_REQUEST)));

        $this->expectException(TokenRevokeException::class);
        $response = $client->revokeToken(Token::createFromAccessToken($this->faker->accessToken()));
    }

    /**
     * @throws Exception
     */
    public function testValidateToken()
    {
        $clientId = $this->faker->id();
        $scopes = [];
        $expiresIn = $this->faker->numberBetween(0, 5200000);

        $client = $this->setupClient(MockClient::requests(MockJsonResponse::make(['client_id' => $clientId, 'scopes' => $scopes, 'expires_in' => $expiresIn])));

        $response = $client->validateToken(Token::createFromAccessToken($this->faker->accessToken()));
        $this->assertInstanceOf(TokenValidationResponseInterface::class, $response);
        $this->assertEquals($clientId, $response->getClientId());
        $this->assertCount(0, $response->getScopes());
        $this->assertTrue($response->isAppToken());
        $this->assertFalse($response->hasExpired());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testExchange()
    {
        $client = $this->setupClient(MockClient::requests(MockJsonResponse::makeFixture('HttpClient/get-user-token-success.json')));

        $response = $client->exchange($this->faker->accessToken(), url: 'https://www.example.com');
        $this->assertInstanceOf(AccessTokenInterface::class, $response);

        $token = Token::createFromParts(accessToken: 'vkhp8pva30rb0df4z80fesvarzpxn8', refreshToken: 'svavkhp8pva38pv30rb0df40rb0dsvaf4z80fessvavarpxn8', expiresIn: 13573,
            scope: 'user:read:email', tokenType: 'bearer', tokenSource: TokenSource::user, identifier: 'TWITCH');

        $this->assertTokenEquals($token, $response);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testExchangeInvalid()
    {
        $this->expectException(EmptyContentException::class);

        $client = $this->setupClient(MockClient::empty());
        $client->exchange($this->faker->accessToken(), url: 'https://www.example.com');
    }

    /**
     *
     */
    public function testClientAnnotations()
    {
        $client = $this->setupClient();
        $this->assertClientAnnotationEquals('TWITCH', TokenSource::user, $client);
    }

    /**
     *
     */
    public function testUsesClientAnnotations()
    {
        $this->assertUsesClientAnnotations($this->setupClient());
    }
}