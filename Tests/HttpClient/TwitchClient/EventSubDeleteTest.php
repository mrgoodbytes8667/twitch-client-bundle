<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Response as Http;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class EventSubDeleteTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
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

        $dispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $client = $this->setupClient(MockClient::empty(), $dispatcher);

        $client->eventSubDelete($this->faker->uuid())->onSuccessCallback();
    }
}