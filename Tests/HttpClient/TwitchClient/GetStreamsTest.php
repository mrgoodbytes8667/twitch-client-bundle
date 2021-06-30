<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockExceededRateLimitResponse;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockStandaloneResponse;
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
 * Class GetStreamsTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
class GetStreamsTest extends TestTwitchClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @dataProvider provideStreams
     * @dataProvider provideTooManyUserIds
     * @param $userIds
     * @param $logins
     * @param $gameIds
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetStreamsClientSuccess($userIds, $logins, $gameIds)
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-streams-success.json')));

        $response = $client->getStreams(gameIds: $gameIds, userIds: $userIds, logins: $logins, throw: false);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-streams-success.json'));
    }



    /**
     * @return Generator
     */
    public function provideStreams()
    {
        $this->setupFaker();
        $userIds[] = [];
        $logins[] = [];
        $gameIds[] = [];

        $userIds[] = [$this->faker->unique()->randomNumber(7, true), $this->faker->unique()->randomNumber(7, true), $this->faker->unique()->randomNumber(7, true)];
        $logins[] = [$this->faker->unique()->userName(), $this->faker->unique()->userName(), $this->faker->unique()->userName()];
        $gameIds[] = [$this->faker->unique()->randomNumber(5, true), $this->faker->unique()->randomNumber(5, true), $this->faker->unique()->randomNumber(5, true)];

        foreach($userIds as $userId)
        {
            foreach($logins as $login)
            {
                foreach($gameIds as $gameId)
                {
                    yield ['userIds' => $userId, 'logins' => $login, 'gameIds' => $gameId];
                }
            }
        }
    }

    /**
     * @return Generator
     */
    public function provideTooManyUserIds()
    {
        yield['userIds' => range(100, 205), 'logins' => [], 'gameIds' => []];
    }

    /**
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     */
    public function testGetUsersClientTooManyUserIDsArgumentsFailure()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-streams-success.json')));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There can only be a maximum of 100 combined user ids and logins.');

        $ids = range(100, 205);

        $client->getStreams(userIds: $ids);
    }

    /**
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     */
    public function testGetUsersClientTooManyUserLoginsArgumentsFailure()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-streams-success.json')));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There can only be a maximum of 100 combined user ids and logins.');

        $logins = $this->faker->words(101);

        $client->getStreams(logins: $logins);
    }

    /**
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     */
    public function testGetUsersClientTooManyGameArgumentsFailure()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-streams-success.json')));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There can only be a maximum of 100 game ids.');

        $ids = range(100, 205);

        $client->getStreams(gameIds: $ids);
    }
}