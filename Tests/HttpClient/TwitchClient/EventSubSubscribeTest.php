<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Subscriptions;
use Generator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class EventSubSubscribeTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
class EventSubSubscribeTest extends TestTwitchClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @dataProvider provideEventSubSubscribes
     * @param $type
     * @param $user
     * @param $callback
     * @param $extraConditions
     * @return ClientResponseInterface
     * @throws TransportExceptionInterface
     */
    public function testEventSubSubscribeClientSuccess($type, $user, $callback, $extraConditions)
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $dispatcher->expects($this->once())
            ->method('dispatch');

        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/eventsub-subscribe-success.json')), $dispatcher);

        $cmd = $client->eventSubSubscribe(EventSubSubscriptionTypes::streamOnline(), $this->createMockUser(), $callback, []);
        $this->assertResponseIsSuccessful($cmd);
        $this->assertResponseStatusCodeSame($cmd, Response::HTTP_OK);

        return $cmd;
    }

    /**
     * @return Generator
     */
    public function provideEventSubSubscribes()
    {
        $this->setupFaker();

        yield ['type' => null, 'user' => null, 'callback' => $this->faker->url(), 'extraConditions' => []];
        yield ['type' => null, 'user' => null, 'callback' => null, 'extraConditions' => []];
        yield ['type' => null, 'user' => null, 'callback' => function ($a, $b) {
            return $this->faker->url();
        }, 'extraConditions' => []];
    }

    /**
     *
     */
    public function testEventSubSubscribeResponseDeserialize()
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/eventsub-subscribe-success.json')), $dispatcher);

        $response = $client->eventSubSubscribe(EventSubSubscriptionTypes::streamOnline(), $this->createMockUser(), $this->faker->url(), []);

        /** @var Subscriptions $subscriptions */
        $subscriptions = $response->deserialize();
        $subscription = $subscriptions->getSubscription();
        $this->assertNotEmpty($subscriptions);
    }
}