<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockExceededRateLimitResponse;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockStandaloneResponse;
use Bytes\TwitchResponseBundle\Objects\Videos\VideosResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 *
 */
class GetVideosTest extends TestTwitchClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetVideosClientSuccess()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-videos-success.json')));

        $response = $client->getVideos(userId: '123', limit: 20);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-videos-success.json'));
    }

    /**
     * @return ClientResponseInterface
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetVideosClientFailureInvalidname()
    {
        $client = $this->setupClient(MockClient::emptyData());

        $response = $client->getVideos(userId: 'abc', limit: 20);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, '{"data": []}');

        return $response;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetVideoResponse()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-videos-success.json')));

        /** @var VideosResponse $response */
        $response = $client->getVideos(userId: '222516945777')->deserialize();

        $this->assertCount(5, $response->getData());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetVideoResponseWithRetry()
    {
        $client = $this->setupClient(MockClient::requests(
            MockExceededRateLimitResponse::make(),
            MockJsonResponse::makeFixture('HttpClient/get-videos-success.json')));

        /** @var VideosResponse $response */
        $response = $client->getVideos(userId: '222516945777')->deserialize();

        $this->assertCount(5, $response->getData());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws NoTokenException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetVideoClientFailureRateLimitTooManyRetries()
    {
        $client = $this->setupClient(MockClient::rateLimit());

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage('HTTP 429 returned for');

        /** @var VideosResponse $response */
        $response = $client->getVideos(userId: '222516945777')->deserialize();
    }

    /**
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     */
    public function testGetVideoClientFailureServiceUnavailableTooManyRetries()
    {
        // 503 should only be retried once, so the success should not be reached
        $client = $this->setupClient(MockClient::requests(
            new MockStandaloneResponse(statusCode: Response::HTTP_SERVICE_UNAVAILABLE),
            new MockStandaloneResponse(statusCode: Response::HTTP_SERVICE_UNAVAILABLE),
            MockJsonResponse::makeFixture('HttpClient/get-videos-success.json')));

        $response = $client->getVideos(userId: '222516945777');

        $this->assertResponseStatusCodeSame($response, Response::HTTP_SERVICE_UNAVAILABLE);
    }
}