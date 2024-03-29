<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchEventSubClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchResponseBundle\Enums\EventSubStatus;
use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Subscriptions;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 *
 */
class LegacyEventSubGetSubscriptionsTest extends TestTwitchEventSubClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @throws TransportExceptionInterface
     * @throws NoTokenException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testEventSubGetSubscriptionsClientSuccess()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/eventsub-list-1-success.json')));

        $response = $client->eventSubGetSubscriptions(followPagination: false);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/eventsub-list-1-success.json'));
        $this->assertEquals('https://api.twitch.tv/helix/eventsub/subscriptions', $response->getResponse()->getInfo('url'));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws NoTokenException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testEventSubGetSubscriptionsWithPaginationClientSuccess()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/eventsub-list-1-pagination-success.json'),
            MockJsonResponse::makeFixture('HttpClient/eventsub-list-1-success.json')));

        $response = $client->eventSubGetSubscriptions(followPagination: true);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
    }

    /**
     * @dataProvider provideValidFilters
     * @param $filter
     * @throws TransportExceptionInterface
     * @throws NoTokenException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testEventSubGetSubscriptionsWithArgumentsClientSuccess($filter)
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/eventsub-list-1-success.json')));

        $response = $client->eventSubGetSubscriptions($filter);
        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/eventsub-list-1-success.json'));
        $this->assertStringStartsWith('https://api.twitch.tv/helix/eventsub/subscriptions', $response->getResponse()->getInfo('url'));
    }

    /**
     * @return Generator
     */
    public function provideValidFilters()
    {
        yield ['filter' => null];
        foreach (EventSubSubscriptionTypes::values() as $value) {
            $type = EventSubSubscriptionTypes::from($value);
            yield ['filter' => $type];
        }
        foreach (EventSubStatus::values() as $value) {
            $status = EventSubStatus::from($value);
            yield ['filter' => $status];
        }
    }

    /**
     *
     */
    public function testEventSubGetSubscriptionsResponseDeserialize()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/eventsub-list-1-success.json')));

        $response = $client->eventSubGetSubscriptions();

        /** @var Subscriptions $subscriptions */
        $subscriptions = $response->deserialize();
        $this->assertEquals(1, $subscriptions->getTotal());
        $this->assertEquals(0, $subscriptions->getTotalCost());
        $this->assertEquals(10000, $subscriptions->getMaxTotalCost());

        $subscription = $subscriptions->getSubscription();
        $this->assertEquals('enabled', $subscription->getStatus());
        $this->assertEquals('channel.update', $subscription->getType());
        $this->assertEquals('4d1k7355-157e-7o28-oe16-72d1e0e07927', $subscription->getId());
        $this->assertEquals(0, $subscription->getCost());
    }


    /**
     * @throws TransportExceptionInterface
     * @throws NoTokenException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testEventSubGetSubscriptionsWithPaginationResponse()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/eventsub-list-1-pagination-success.json'),
            MockJsonResponse::makeFixture('HttpClient/eventsub-list-1-success.json')));

        /** @var Subscriptions $response */
        $response = $client->eventSubGetSubscriptions(followPagination: true)->deserialize();
        $this->assertCount(2, $response->getData());
    }
}