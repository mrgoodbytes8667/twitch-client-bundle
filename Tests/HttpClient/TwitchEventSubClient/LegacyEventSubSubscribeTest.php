<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchEventSubClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionCreatePreRequestEvent;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionGenerateCallbackEvent;
use Bytes\TwitchClientBundle\Exception\EventSubSubscriptionException;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Subscriptions;
use Bytes\TwitchResponseBundle\Objects\Interfaces\UserInterface;
use Generator;
use InvalidArgumentException;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * 
 */
class LegacyEventSubSubscribeTest extends TestTwitchEventSubClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @dataProvider provideEventSubSubscribes
     * @dataProvider provideEventSubSubscribesClient
     * @param $type
     * @param $user
     * @param $callback
     * @param $extraConditions
     * @return ClientResponseInterface
     * @throws TransportExceptionInterface
     * @throws NoTokenException
     */
    public function testEventSubSubscribeClientSuccess($type, $user, $callback, $extraConditions)
    {
        $event = $this->createPreEvent($type, $user, $callback);
        $dispatcher = $this->createMock(EventDispatcher::class);
        $callbackEvent = EventSubSubscriptionGenerateCallbackEvent::new('', $type, $user);
        $callbackEvent->setUrl($this->faker->url());

        $dispatcherCount = 1;
        if (is_null($callback)) {
            $dispatcherCount = 2;
        }

        $dispatcher->expects($this->exactly($dispatcherCount))
            ->method('dispatch')
            ->will($this->returnCallback(function ($e) use ($callbackEvent, $event) {
                if ($e instanceof EventSubSubscriptionCreatePreRequestEvent) {
                    return $event;
                } else {
                    return $callbackEvent;
                }
            }));

        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/eventsub-subscribe-success.json')), $dispatcher)
            ->setEventSubSubscriptionGenerateCallbackEvent($callbackEvent);

        $cmd = $client->eventSubSubscribe($type, $this->createMockUser(), $callback, []);
        $this->assertResponseIsSuccessful($cmd);
        $this->assertResponseStatusCodeSame($cmd, Response::HTTP_OK);

        return $cmd;
    }

    /**
     * @param $type
     * @param $user
     * @param $callback
     * @return EventSubSubscriptionCreatePreRequestEvent
     */
    protected function createPreEvent($type, $user, $callback)
    {
        if (is_string($callback)) {
            $url = $callback;
        } elseif (is_null($callback)) {
            $url = $this->faker->url();
        } elseif (is_callable($callback)) {
            $url = call_user_func($callback, $type, $user);
        } else {
            $url = $this->faker->url();
        }
        $event = EventSubSubscriptionCreatePreRequestEvent::make($type, $user, $url);
        $event->setEntity(new stdClass());
        return $event;
    }

    /**
     * @dataProvider provideEventSubSubscribes
     * @param $type
     * @param $user
     * @param $callback
     * @param $extraConditions
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     */
    public function testEventSubSubscribeClientNoEntity($type, $user, $callback, $extraConditions)
    {
        $this->expectException(EventSubSubscriptionException::class);
        $this->expectExceptionMessage('Unable to save new subscription');

        $event = $this->createPreEvent($type, $user, $callback);
        $event->setEntity(null);
        $dispatcher = $this->createMock(EventDispatcher::class);
        $callbackEvent = EventSubSubscriptionGenerateCallbackEvent::new('', EventSubSubscriptionTypes::channelUpdate(), $user);
        $callbackEvent->setUrl($this->faker->url());

        $dispatcherCount = 1;
        if (is_null($callback)) {
            $dispatcherCount = 2;
        }

        $dispatcher->expects($this->exactly($dispatcherCount))
            ->method('dispatch')
            ->will($this->returnCallback(function ($e) use ($callbackEvent, $event) {
                if ($e instanceof EventSubSubscriptionCreatePreRequestEvent) {
                    return $event;
                } else {
                    return $callbackEvent;
                }
            }));

        $client = $this->setupClient(dispatcher: $dispatcher)
            ->setEventSubSubscriptionGenerateCallbackEvent($callbackEvent);

        $client->eventSubSubscribe($type, $this->createMockUser(), $callback, []);
    }

    /**
     * @dataProvider provideUnsupportedTypes
     * @param $type
     * @throws NoTokenException
     * @throws TransportExceptionInterface
     */
    public function testEventSubSubscribeClientUnsupportedType($type)
    {
        $this->expectException(InvalidArgumentException::class);
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
        $user = $this->getMockBuilder(UserInterface::class)->getMock();

        foreach (self::getSupportedTypesUser() as $type) {

            yield ['type' => $type, 'user' => $user, 'callback' => $this->faker->url(), 'extraConditions' => []];
            yield ['type' => $type, 'user' => $user, 'callback' => null, 'extraConditions' => []];
            yield ['type' => $type, 'user' => $user, 'callback' => function ($a, $b) {
                return $this->faker->url();
            }, 'extraConditions' => []];
        }
    }

    /**
     * @return Generator
     */
    public function provideEventSubSubscribesClient()
    {
        $this->setupFaker();

        foreach (self::getSupportedTypesClient() as $type) {

            yield ['type' => $type, 'user' => null, 'callback' => $this->faker->url(), 'extraConditions' => []];
            yield ['type' => $type, 'user' => null, 'callback' => null, 'extraConditions' => []];
            yield ['type' => $type, 'user' => null, 'callback' => function ($a, $b) {
                return $this->faker->url();
            }, 'extraConditions' => []];
        }
    }

    /**
     * @return array
     */
    protected static function getSupportedTypesUser()
    {
        return [
            EventSubSubscriptionTypes::channelUpdate(),
            EventSubSubscriptionTypes::streamOnline(),
            EventSubSubscriptionTypes::streamOffline(),
            EventSubSubscriptionTypes::channelSubscribe(),
            EventSubSubscriptionTypes::channelPointsCustomRewardAdd(),
            EventSubSubscriptionTypes::userUpdate(),
        ];
    }

    /**
     * @return array
     */
    protected static function getSupportedTypesClient()
    {
        return [
            EventSubSubscriptionTypes::userAuthorizationGrant(),
            EventSubSubscriptionTypes::userAuthorizationRevoke(),
        ];
    }

    /**
     * @return Generator
     */
    public function provideUnsupportedTypes()
    {
        foreach (EventSubSubscriptionTypes::toValues() as $value) {
            $type = EventSubSubscriptionTypes::from($value);
            if (!in_array($type, self::getSupportedTypesUser()) && !in_array($type, self::getSupportedTypesClient())) {
                yield ['type' => $type];
            }
        }
    }

    /**
     *
     */
    public function testEventSubSubscribeResponseDeserialize()
    {
        $type = EventSubSubscriptionTypes::streamOnline();
        $user = $this->createMockUser();
        $callback = $this->faker->url();
        $event = $this->createPreEvent($type, $user, $callback);
        $dispatcher = $this->createMock(EventDispatcher::class);

        $dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturn($event);

        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/eventsub-subscribe-success.json')), $dispatcher);

        $response = $client->eventSubSubscribe($type, $user, $callback, []);

        /** @var Subscriptions $subscriptions */
        $subscriptions = $response->deserialize();
        $subscription = $subscriptions->getSubscription();
        $this->assertNotEmpty($subscriptions);
    }
}