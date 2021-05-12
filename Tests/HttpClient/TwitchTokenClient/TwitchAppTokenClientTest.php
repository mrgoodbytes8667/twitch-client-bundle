<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchTokenClient;

use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Enums\TokenSource;
use Bytes\ResponseBundle\Interfaces\ClientTokenResponseInterface;
use Bytes\ResponseBundle\Test\AssertClientAnnotationsSameTrait;
use Bytes\ResponseBundle\Token\Interfaces\AccessTokenInterface;
use Bytes\ResponseBundle\Token\Interfaces\TokenValidationResponseInterface;
use Bytes\Tests\Common\MockHttpClient\MockEmptyResponse;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestHttpClientCase;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestTwitchClientTrait;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchClientBundle\Tests\TestUrlGeneratorTrait;
use Bytes\TwitchClientBundle\Tests\TwitchClientSetupTrait;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
use Exception;
use LogicException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class TwitchAppTokenClientTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchTokenClient
 */
class TwitchAppTokenClientTest extends TestHttpClientCase
{
    use AssertClientAnnotationsSameTrait, TestTwitchClientTrait, TestTwitchFakerTrait, TestUrlGeneratorTrait, TwitchClientSetupTrait {
        TwitchClientSetupTrait::setupAppTokenClient as setupClient;
    }

    /**
     *
     */
    public function testGetToken()
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/get-app-token-success.json')
        ]));

        $token = $client->getToken();
        $this->assertInstanceOf(AccessTokenInterface::class, $token);
        $this->assertEquals('vkhp8pva30rb0df4z80fesvarzpxn8', $token->getAccessToken());
    }

    /**
     * @throws Exception
     */
    public function testRefreshToken()
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/get-app-token-success.json')
        ]));

        $token = $client->refreshToken(Token::createFromAccessToken($this->faker->accessToken()));
        $this->assertInstanceOf(AccessTokenInterface::class, $token);
        $this->assertEquals('vkhp8pva30rb0df4z80fesvarzpxn8', $token->getAccessToken());
    }

    /**
     * @dataProvider provideClientExceptionResponses
     * @param $code
     * @throws Exception
     */
    public function testRefreshTokenError($code)
    {
        $client = $this->setupClient(MockClient::emptyError($code));

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

        $response = $client->revokeToken(Token::createFromAccessToken($this->faker->accessToken()));
        $this->assertResponseStatusCodeSame($response, Response::HTTP_BAD_REQUEST);
        $this->assertResponseHasContent($response);
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
     *
     */
    public function testExchange()
    {
        $this->expectException(LogicException::class);

        $client = $this->setupClient(MockClient::empty());
        $client->exchange($this->faker->accessToken());
    }

    /**
     *
     */
    public function testClientAnnotations()
    {
        $client = $this->setupClient();
        $this->assertClientAnnotationEquals('TWITCH', TokenSource::app(), $client);
    }

    /**
     *
     */
    public function testUsesClientAnnotations()
    {
        $this->assertUsesClientAnnotations($this->setupClient());
    }
}