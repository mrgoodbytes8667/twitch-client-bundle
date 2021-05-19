<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchEventSubClient;


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
class EventSubSubscribeTest extends TestTwitchEventSubClientCase
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

        $cmd = $client->eventSubSubscribe($type, $this->createMockUser(), $callback, []);
        $this->assertResponseIsSuccessful($cmd);
        $this->assertResponseStatusCodeSame($cmd, Response::HTTP_OK);

        return $cmd;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testEventSubSubscribeClientInvalidCallback()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The argument "callback" must be a valid URL, a callback, or null.');
        $this->setupClient()->eventSubSubscribe(EventSubSubscriptionTypes::streamOnline(), $this->createMockUser(), new \stdClass(), []);
    }

    /**
     * @dataProvider provideUnsupportedTypes
     * @param $type
     * @throws TransportExceptionInterface
     */
    public function testEventSubSubscribeClientUnsupportedType($type)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('The type "%s" is not yet supported', $type));
        $client = $this->setupClient();
        $client->eventSubSubscribe($type, $this->createMockUser(), $this->faker->url(), []);
    }

    /**
     * @return Generator
     */
    public function provideEventSubSubscribes()
    {
        $this->setupFaker();

        foreach(self::getSupportedTypes() as $type) {

            yield ['type' => $type, 'user' => null, 'callback' => $this->faker->url(), 'extraConditions' => []];
            yield ['type' => $type, 'user' => null, 'callback' => null, 'extraConditions' => []];
            yield ['type' => $type, 'user' => null, 'callback' => function ($a, $b) {
                return $this->faker->url();
            }, 'extraConditions' => []];
        }
    }

    protected static function getSupportedTypes()
    {
        return [EventSubSubscriptionTypes::channelUpdate(), EventSubSubscriptionTypes::streamOnline(), EventSubSubscriptionTypes::streamOffline(), EventSubSubscriptionTypes::channelSubscribe(), EventSubSubscriptionTypes::channelChannelPointsCustomRewardAdd(), EventSubSubscriptionTypes::userUpdate()];
    }

    public function provideUnsupportedTypes()
    {
        foreach (EventSubSubscriptionTypes::getValues() as $value)
        {
            $type = EventSubSubscriptionTypes::make($value);
            if(!in_array($type, self::getSupportedTypes()))
            {
                yield ['type' => $type];
            }
        }
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