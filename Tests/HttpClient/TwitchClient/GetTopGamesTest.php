<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockExceededRateLimitResponse;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockStandaloneResponse;
use Bytes\TwitchResponseBundle\Objects\Games\Game;
use Bytes\TwitchResponseBundle\Objects\Games\GamesResponse;
use Bytes\TwitchResponseBundle\Objects\Users\User;
use DateTime;
use DateTimeInterface;
use Generator;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class GetTopGamesTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
class GetTopGamesTest extends TestTwitchClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetTopGamesClientSuccess()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-top-games-success.json')));

        $response = $client->getTopGames(limit: 100);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-top-games-success.json'));
    }

    /**
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     */
    public function testGetTopGamesClientFailureLimitTooLow()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-top-games-success.json')));

        $this->expectException(InvalidArgumentException::class);

        $response = $client->getTopGames(limit: 0);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetTopGamesClientLimitTooLow()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-top-games-limit-1-success.json')));

        $response = $client->getTopGames(limit: 0, throw: false);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-top-games-limit-1-success.json'));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetTopGamesDeserializeSuccess()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-top-games-success.json')));

        /** @var GamesResponse $response */
        $response = $client->getTopGames(limit: 100)->deserialize();
        $this->assertCount(100, $response->getData());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetTopGamesDeserializeRecursiveSuccess()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-top-games-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-top-games-2-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-top-games-3-success.json')));

        /** @var GamesResponse $response */
        $response = $client->getTopGames(limit: 250)->deserialize();
        $this->assertCount(250, $response->getData());
    }
}