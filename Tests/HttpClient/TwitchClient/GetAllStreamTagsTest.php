<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchResponseBundle\Objects\Tags\TagsResponse;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class GetAllStreamTagsTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
class GetAllStreamTagsTest extends TestTwitchClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetAllStreamTagsClientSuccess()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-generated-1-success.json')));

        $response = $client->getAllStreamTags();
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-all-stream-tags-generated-1-success.json'));
    }

    /**
     * @dataProvider provideTagIds
     * @param $tagIds
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws NoTokenException
     */
    public function testGetAllStreamTagsWithTagIdsClientSuccess($tagIds)
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-generated-1-success.json')));

        $response = $client->getAllStreamTags(ids: $tagIds);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-all-stream-tags-generated-1-success.json'));
    }

    /**
     * @return Generator
     */
    public function provideTagIds()
    {
        yield ['621fb5bf-5498-4d8f-b4ac-db4d40d401bf'];
        yield [['621fb5bf-5498-4d8f-b4ac-db4d40d401bf']];
        yield [['621fb5bf-5498-4d8f-b4ac-db4d40d401bf', '20986b7d-c78f-4a94-a798-563c136ae21e']];
        yield [[]];
        yield [null];
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetAllStreamTagsResponseRecursiveSuccess()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-1-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-2-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-3-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-4-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-5-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-6-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-7-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-8-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-9-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-10-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-11-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-12-success.json')));

        /** @var TagsResponse $tags */
        $tags = $client->getAllStreamTags(limit: 1006, followPagination: true)->deserialize();
        $this->assertCount(1006, $tags->getData());
        $this->assertEmpty($tags->getPagination());
    }

    /**
     * @dataProvider provideTagIds
     * @param $tagIds
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetAllStreamTagsWithTagIdsResponseRecursiveSuccess($tagIds)
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-1-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-2-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-3-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-4-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-5-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-6-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-7-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-8-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-9-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-10-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-11-success.json'),
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-12-success.json')));

        /** @var TagsResponse $tags */
        $tags = $client->getAllStreamTags(ids: $tagIds, limit: 1006, followPagination: true)->deserialize();
        $this->assertCount(1006, $tags->getData());
        $this->assertEmpty($tags->getPagination());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetAllStreamTagsResponseNonRecursiveSuccess()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-generated-1-success.json')));

        /** @var TagsResponse $tags */
        $tags = $client->getAllStreamTags(followPagination: false)->deserialize();
        $this->assertCount(100, $tags->getData());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetAllStreamTagsResponseNegativeLimit()
    {
        $this->expectException(\InvalidArgumentException::class);
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-generated-1-success.json')));

        /** @var TagsResponse $tags */
        $tags = $client->getAllStreamTags(limit: -1)->deserialize();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetAllStreamTagsResponseNegativeLimitNoThrow()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-generated-1-success.json')));

        /** @var TagsResponse $tags */
        $tags = $client->getAllStreamTags(limit: -1, throw: false, followPagination: false)->deserialize();
        $this->assertCount(100, $tags->getData());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetAllStreamTagsResponseTooManyTags()
    {
        $ids = [];
        foreach (range(1, 101) as $index)
        {
            $ids[] = $this->faker->uuid();
        }
        
        $this->expectException(\InvalidArgumentException::class);
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-generated-1-success.json')));

        $client->getAllStreamTags(ids: $ids)->deserialize();
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetAllStreamTagsResponseTooManyTagsNoThrow()
    {
        $ids = [];
        foreach (range(1, 101) as $index)
        {
            $ids[] = $this->faker->uuid();
        }

        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-all-stream-tags-generated-1-success.json')));

        /** @var TagsResponse $tags */
        $tags = $client->getAllStreamTags(ids: $ids, throw: false)->deserialize();
        $this->assertCount(100, $tags->getData());
    }

}