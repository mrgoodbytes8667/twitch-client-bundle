<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchResponseBundle\Objects\Tags\Tag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class GetStreamTagsTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
class GetStreamTagsTest extends TestTwitchClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetStreamTagsClientSuccess()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-stream-tags-success.json')));

        $response = $client->getStreamTags($this->faker->id());
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-stream-tags-success.json'));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetStreamTagsClientOfflineSuccess()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-stream-tags-empty-success.json')));

        $response = $client->getStreamTags($this->faker->id());
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-stream-tags-empty-success.json'));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetStreamTagsResponse()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-stream-tags-success.json')));

        /** @var Tag[] $response */
        $response = $client->getStreamTags($this->faker->id())->deserialize();

        $this->assertCount(3, $response);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetStreamTagsResponseOffline()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-stream-tags-empty-success.json')));

        /** @var Tag[] $response */
        $response = $client->getStreamTags($this->faker->id())->deserialize();

        $this->assertCount(0, $response);
    }
}