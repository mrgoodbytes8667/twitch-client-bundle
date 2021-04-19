<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\Tests\Common\MockHttpClient\MockClient;
use Bytes\Tests\Common\MockHttpClient\MockEmptyResponse;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Subscriptions;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response as Http;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class EventSubDeleteTest extends TestTwitchClientCase
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
        $dispatcher = $this->createMock(EventDispatcher::class);

        $dispatcher->expects($this->once())
            ->method('dispatch');

        $client = $this->setupClient(MockClient::empty(), $dispatcher);

        $client->eventSubDelete($this->faker->uuid())->callback();
    }
}