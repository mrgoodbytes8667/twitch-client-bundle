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
use Bytes\TwitchResponseBundle\Objects\Users\User;
use Generator;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class GetGamesTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
class GetGamesTest extends TestTwitchClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @dataProvider provideUsers
     * @dataProvider provideTooManyUsers
     * @param $ids
     * @param $names
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetGamesClientSuccess($ids, $names, $igdbIds)
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-games-success.json')));

        $response = $client->getGames(ids: $ids, names: $names, igdbIds: $igdbIds, throw: false);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-games-success.json'));
    }

    /**
     * @dataProvider provideTooManyUsers
     * @param $ids
     * @param $names
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetGamesResponseTooManyArguments($ids, $names, $igdbIds)
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-games-success.json')));

        $users = $client->getGames(ids: $ids, names: $names, igdbIds: $igdbIds, throw: false)->deserialize();

        $this->assertCount(4, $users);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetGamesClientTooManyArgumentsFailure()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-games-success.json')));

        $this->expectException(InvalidArgumentException::class);

        $names = $this->faker->words(101);

        $client->getGames(names: $names);
    }

    /**
     * @return Generator
     */
    public function provideUsers()
    {
        yield ['ids' => ["222516945777", "60387332406", "962819907194", "439941614823"], 'names' => [], 'igdb_ids' => []];
        yield ['ids' => [], 'names' => ["morgan.oberbrunner", "cwitting", "lilliana99", "nkunde"], 'igdb_ids' => []];
        yield ['ids' => ["222516945777", "60387332406"], 'names' => ["lilliana99", "nkunde"], 'igdb_ids' => []];
        yield ['ids' => [], 'names' => [], 'igdb_ids' => ["222516945777", "60387332406", "962819907194", "439941614823"]];
        yield ['ids' => ["222516945777", "60387332406"], 'names' => ["lilliana99", "nkunde"], 'igdb_ids' => ["222516945777", "60387332406"]];
    }

    /**
     * @return Generator
     */
    public function provideTooManyUsers()
    {
        $this->setupFaker();

        yield ['ids' => $this->idRange(1, 101), 'names' => $this->faker->words(101), 'igdb_ids' => []];
        yield ['ids' => $this->idRange(1, 55), 'names' => $this->faker->words(55), 'igdb_ids' => []];
        yield ['ids' => [], 'names' => $this->faker->words(101), 'igdb_ids' => []];
        yield ['ids' => $this->idRange(1, 55), 'names' => $this->faker->words(55), 'igdb_ids' => $this->idRange(1, 55)];
        yield ['ids' => [], 'names' => [], 'igdb_ids' => $this->idRange(1, 101)];
    }

    /**
     * Convert integer range to strings
     * @param int $start
     * @param int $end
     * @return array
     */
    private function idRange(int $start, int $end)
    {
        foreach (range($start, $end) as $index => $value) {
            $return[] = (string)$value;
        }

        return $return;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetGamesClientFailureInvalidname()
    {
        $client = $this->setupClient(MockClient::emptyData());

        $response = $client->getGames(names: [$this->faker->userName()]);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, '{"data": []}');

        return $response;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws NoTokenException
     */
    public function testGetGamesClientFailureIdZero()
    {
        $client = $this->setupClient(MockClient::emptyData());

        $this->expectException(InvalidArgumentException::class);

        $client->getGame(id: '0');
    }

    /**
     * @depends testGetGamesClientFailureInvalidname
     * @param $response
     */
    public function testGetGamesDeserializeFailureInvalidname($response)
    {
        $users = $response->deserialize();

        $this->assertCount(0, $users);
    }

    /**
     * @dataProvider provideUsers
     * @param $ids
     * @param $names
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetGamesDeserialize($ids, $names, $igdbIds)
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-games-success.json')));

        /** @var User[] $users */
        $users = $client->getGames(ids: $ids, names: $names, igdbIds: $igdbIds)->deserialize();
        $this->assertCount(4, $users);

        $user = array_shift($users);
        $this->validateGame($user, "953931171574", "ea consequatur nihil", "https://via.placeholder.com/640x480.png/004499?text=Game+ea+consequatur+nihil", "7888105");

        $user = array_shift($users);
        $this->validateGame($user, "253674780146", "iste ut adipisci", "https://via.placeholder.com/640x480.png/00ff66?text=Game+iste+ut+adipisci", "29223596");

        $user = array_shift($users);
        $this->validateGame($user, "43518325304", "esse quia repudiandae", "https://via.placeholder.com/640x480.png/00bb00?text=Game+esse+quia+repudiandae", "22672905");

        $user = array_shift($users);
        $this->validateGame($user, "278190657748", "consequatur dolores nihil", "https://via.placeholder.com/640x480.png/0077dd?text=Game+consequatur+dolores+nihil", "82459993");
    }

    /**
     * @param Game $game
     * @param string $id
     * @param string $name
     * @param string $boxArtUrl
     * @param string $igdbId
     * @return void
     */
    protected function validateGame(Game $game, string $id, string $name, string $boxArtUrl, string $igdbId)
    {
        $this->assertEquals($id, $game->getGameID());
        $this->assertEquals($name, $game->getName());
        $this->assertEquals($boxArtUrl, $game->getBoxArtURLWithSize(640, 480));
        self::assertEquals($igdbId, $game->getIgdbId());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetGameResponse()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-games-success.json')));

        /** @var User $users */
        $user = $client->getGame(id: '222516945777')->deserialize();

        $this->validateGame($user, "953931171574", "ea consequatur nihil", "https://via.placeholder.com/640x480.png/004499?text=Game+ea+consequatur+nihil", "7888105");
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetGameResponseWithRetry()
    {
        $client = $this->setupClient(MockClient::requests(
            MockExceededRateLimitResponse::make(),
            MockJsonResponse::makeFixture('HttpClient/get-games-success.json')));

        /** @var User $users */
        $user = $client->getGame(id: '222516945777')->deserialize();

        $this->validateGame($user, "953931171574", "ea consequatur nihil", "https://via.placeholder.com/640x480.png/004499?text=Game+ea+consequatur+nihil", "7888105");
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetGameClientFailureRateLimitTooManyRetries()
    {
        $client = $this->setupClient(MockClient::rateLimit());

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage('HTTP 429 returned for');

        $client->getGame(id: '222516945777');
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetGameClientFailureServiceUnavailableTooManyRetries()
    {
        // 503 should only be retried once, so the success should not be reached
        $client = $this->setupClient(MockClient::requests(
            new MockStandaloneResponse(statusCode: Response::HTTP_SERVICE_UNAVAILABLE),
            new MockStandaloneResponse(statusCode: Response::HTTP_SERVICE_UNAVAILABLE),
            MockJsonResponse::makeFixture('HttpClient/get-games-success.json')));

        $response = $client->getGame(id: '222516945777');

        $this->assertResponseStatusCodeSame($response, Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
