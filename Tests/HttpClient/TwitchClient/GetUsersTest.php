<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
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
 * Class GetUsersTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
class GetUsersTest extends TestTwitchClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @dataProvider provideUsers
     * @dataProvider provideTooManyUsers
     * @param $ids
     * @param $logins
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetUsersClientSuccess($ids, $logins)
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-users-success.json')));

        $response = $client->getUsers(ids: $ids, logins: $logins, throw: false);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-users-success.json'));
    }

    /**
     * @dataProvider provideTooManyUsers
     * @param $ids
     * @param $logins
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetUsersResponseTooManyArguments($ids, $logins)
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-100-users-success.json')));

        $users = $client->getUsers(ids: $ids, logins: $logins, throw: false)->deserialize();

        $this->assertCount(100, $users);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetUsersClientTooManyArgumentsFailure()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-users-success.json')));

        $this->expectException(InvalidArgumentException::class);

        $logins = $this->faker->words(101);

        $client->getUsers(logins: $logins);
    }

    /**
     * @return Generator
     */
    public function provideUsers()
    {
        yield ['ids' => ["222516945777", "60387332406", "962819907194", "439941614823"], 'logins' => []];
        yield ['ids' => [], 'logins' => ["morgan.oberbrunner", "cwitting", "lilliana99", "nkunde"]];
        yield ['ids' => ["222516945777", "60387332406"], 'logins' => ["lilliana99", "nkunde"]];
    }

    /**
     * @return Generator
     */
    public function provideTooManyUsers()
    {
        $this->setupFaker();

        yield ['ids' => $this->idRange(1, 101), 'logins' => $this->faker->words(101)];
        yield ['ids' => $this->idRange(1, 55), 'logins' => $this->faker->words(55)];
        yield ['ids' => [], 'logins' => $this->faker->words(101)];
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
    public function testGetUsersClientFailureInvalidLogin()
    {
        $client = $this->setupClient(MockClient::emptyData());

        $response = $client->getUsers(logins: [$this->faker->userName()]);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, '{"data": []}');

        return $response;
    }

    /**
     * @depends testGetUsersClientFailureInvalidLogin
     * @param $response
     */
    public function testGetUsersDeserializeFailureInvalidLogin($response)
    {
        $users = $response->deserialize();

        $this->assertCount(0, $users);
    }

    /**
     * @dataProvider provideUsers
     * @param $ids
     * @param $logins
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testGetUsersDeserialize($ids, $logins)
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-users-success.json')));

        /** @var User[] $users */
        $users = $client->getUsers(ids: $ids, logins: $logins)->deserialize();
        $this->assertCount(4, $users);

        $user = array_shift($users);
        $this->validateUser($user, "",
            new DateTime('2012-11-26T14:49:18.000Z'),
            "Aliquam amet tenetur odio quo incidunt voluptas iure. Iste minima aut minima suscipit numquam autem quia nostrum. Voluptatibus sed qui aut quasi quos. Distinctio ut odit omnis.",
            "Morgan.oberbrunner",
            null, "222516945777",
            "morgan.oberbrunner",
            "https://via.placeholder.com/1920x1080.png/00aa22?text=Offline+morgan.oberbrunner",
            "https://via.placeholder.com/300x300.png/00ddff?text=Profile+morgan.oberbrunner",
            "",
            306068376);

        $user = array_shift($users);
        $this->validateUser($user, "",
            new DateTime('2011-05-21T15:54:37.000Z'),
            "Eum et dolores est debitis. Ipsa iure facilis impedit quia doloremque quasi veritatis. Repellendus animi voluptas dolores unde dolore esse consectetur. Quis non sit cum alias. Autem in dicta dolores consectetur dolor.",
            "cwitting",
            null,
            "60387332406",
            "cwitting",
            "https://via.placeholder.com/1920x1080.png/00ffdd?text=Offline+cwitting",
            "https://via.placeholder.com/300x300.png/00aa22?text=Profile+cwitting",
            "",
            219394575);

        $user = array_shift($users);
        $this->validateUser($user, "partner",
            new DateTime('2014-09-14T05:42:30.000Z'),
            "Distinctio enim et est dolorem eaque. Numquam qui perspiciatis qui sapiente accusantium incidunt et consectetur. Et natus praesentium sit suscipit voluptatibus.",
            "lilliana99",
            null,
            "962819907194",
            "lilliana99",
            "https://via.placeholder.com/1920x1080.png/00eecc?text=Offline+lilliana99",
            "https://via.placeholder.com/300x300.png/0044cc?text=Profile+lilliana99",
            "",
            1656319518);

        $user = array_shift($users);
        $this->validateUser($user, "affiliate",
            new DateTime('2019-10-16T00:52:52.000Z'),
            "Aut rerum ad odit sapiente suscipit et doloribus. Ratione et et mollitia sed ea. Reiciendis qui iure rerum qui.",
            "nkunde",
            null,
            "439941614823",
            "nkunde",
            "https://via.placeholder.com/1920x1080.png/00ffcc?text=Offline+nkunde",
            "https://via.placeholder.com/300x300.png/00bb00?text=Profile+nkunde",
            "staff",
            415185944);
    }

    /**
     * @param User $user
     * @param string $broadcaster_type
     * @param DateTimeInterface $created_at
     * @param string $description
     * @param string $display_name
     * @param string|null $email
     * @param string $id
     * @param string $login
     * @param string $offline_image_url
     * @param string $profile_image_url
     * @param string $type
     * @param int $view_count
     */
    protected function validateUser(User $user, string $broadcaster_type, DateTimeInterface $created_at, string $description, string $display_name, ?string $email, string $id, string $login, string $offline_image_url, string $profile_image_url, string $type, int $view_count)
    {
        $this->assertEquals($broadcaster_type, $user->getBroadcasterType());
        $this->assertEquals($created_at, $user->getCreatedAt());
        $this->assertEquals($description, $user->getDescription());
        $this->assertEquals($display_name, $user->getDisplayName());
        $this->assertEquals($id, $user->getUserId());
        $this->assertEquals($login, $user->getLogin());
        $this->assertEquals($offline_image_url, $user->getOfflineImageUrl());
        $this->assertEquals($profile_image_url, $user->getProfileImageUrl());
        $this->assertEquals($type, $user->getType());
        $this->assertEquals($view_count, $user->getViewCount());

        if (empty($email)) {
            $this->assertNull($user->getEmail());
        } else {
            $this->assertEquals($email, $user->getEmail());
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetUserResponse()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-users-success.json')));

        /** @var User $users */
        $user = $client->getUser(id: '222516945777')->deserialize();

        $this->validateUser($user, "",
            new DateTime('2012-11-26T14:49:18.000Z'),
            "Aliquam amet tenetur odio quo incidunt voluptas iure. Iste minima aut minima suscipit numquam autem quia nostrum. Voluptatibus sed qui aut quasi quos. Distinctio ut odit omnis.",
            "Morgan.oberbrunner",
            null, "222516945777",
            "morgan.oberbrunner",
            "https://via.placeholder.com/1920x1080.png/00aa22?text=Offline+morgan.oberbrunner",
            "https://via.placeholder.com/300x300.png/00ddff?text=Profile+morgan.oberbrunner",
            "",
            306068376);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetUserResponseWithRetry()
    {
        $client = $this->setupClient(MockClient::requests(
            MockExceededRateLimitResponse::make(),
            MockJsonResponse::makeFixture('HttpClient/get-users-success.json')));

        /** @var User $users */
        $user = $client->getUser(id: '222516945777')->deserialize();

        $this->validateUser($user, "",
            new DateTime('2012-11-26T14:49:18.000Z'),
            "Aliquam amet tenetur odio quo incidunt voluptas iure. Iste minima aut minima suscipit numquam autem quia nostrum. Voluptatibus sed qui aut quasi quos. Distinctio ut odit omnis.",
            "Morgan.oberbrunner",
            null, "222516945777",
            "morgan.oberbrunner",
            "https://via.placeholder.com/1920x1080.png/00aa22?text=Offline+morgan.oberbrunner",
            "https://via.placeholder.com/300x300.png/00ddff?text=Profile+morgan.oberbrunner",
            "",
            306068376);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetUserClientFailureRateLimitTooManyRetries()
    {
        $client = $this->setupClient(MockClient::rateLimit());

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage('HTTP 429 returned for');

        $client->getUser(id: '222516945777');
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testGetUserClientFailureServiceUnavailableTooManyRetries()
    {
        // 503 should only be retried once, so the success should not be reached
        $client = $this->setupClient(MockClient::requests(
            new MockStandaloneResponse(statusCode: Response::HTTP_SERVICE_UNAVAILABLE),
            new MockStandaloneResponse(statusCode: Response::HTTP_SERVICE_UNAVAILABLE),
            MockJsonResponse::makeFixture('HttpClient/get-users-success.json')));

        $response = $client->getUser(id: '222516945777');

        $this->assertResponseStatusCodeSame($response, Response::HTTP_SERVICE_UNAVAILABLE);
    }
}