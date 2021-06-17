<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchEventSubClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionDeleteEvent;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response as Http;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class EventSubDeleteTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
class EventSubDeleteTest extends TestTwitchEventSubClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @throws TransportExceptionInterface
     */
    public function testEventSubDeleteClientSuccess()
    {
        $client = $this->setupClient(MockClient::empty());

        $cmd = $client->eventSubDelete($this->faker->uuid());

        $this->assertResponseIsSuccessful($cmd);
        $this->assertResponseStatusCodeSame($cmd, Http::HTTP_NO_CONTENT);

        return $cmd;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testEventSubDeleteResponseCallback()
    {
        $event = EventSubSubscriptionDeleteEvent::make($this->faker->accessToken());
        $dispatcher = $this->createMock(EventDispatcher::class);

        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturn($event);

        $client = $this->setupClient(MockClient::empty(), $dispatcher);

        $client->eventSubDelete($this->faker->uuid())->onSuccessCallback();
    }
}