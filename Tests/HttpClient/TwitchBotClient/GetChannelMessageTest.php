<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient;

use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchResponseBundle\Objects\Interfaces\ChannelIdInterface;
use Bytes\TwitchResponseBundle\Objects\Interfaces\IdInterface;
use Bytes\TwitchResponseBundle\Objects\Message;
use Faker\Generator;
use InvalidArgumentException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class GetChannelMessageTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient
 */
class GetChannelMessageTest extends TestTwitchBotClientCase
{
    use GuildProviderTrait, MessageProviderTrait;

    /**
     * @dataProvider provideValidChannelMessage
     */
    public function testGetChannelMessage($message, $channel)
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/get-channel-message-success.json'),
        ]));

        $response = $client->getChannelMessage($message, $channel);

        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/get-channel-message-success.json'));
    }

    /**
     * @return Generator
     */
    public function provideValidChannelMessage()
    {
        $message = new Message();
        $message->setId('123');
        $message->setChannelID('456');
        yield ['message' => $message, 'channel' => null];
        yield ['message' => $message, 'channel' => '']; // Still valid since channel is ignored here

        $message = $this
            ->getMockBuilder(IdInterface::class)
            ->getMock();
        $message->method('getId')
            ->willReturn('230858112993375816');

        $channel = $this
            ->getMockBuilder(IdInterface::class)
            ->getMock();
        $channel->method('getId')
            ->willReturn('230858112993375816');
        yield ['message' => $message, 'channel' => $channel];

        $channel = $this
            ->getMockBuilder(ChannelIdInterface::class)
            ->getMock();
        $channel->method('getChannelId')
            ->willReturn('230858112993375816');
        yield ['message' => $message, 'channel' => $channel];
        yield ['message' => '123', 'channel' => '456'];
    }

    /**
     * @dataProvider provideClientExceptionResponses
     *
     * @param int $code
     * @throws TransportExceptionInterface
     */
    public function testGetChannelMessageFailure(int $code)
    {
        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('HTTP %d returned for', $code));

        $client = $this->setupClient(MockClient::emptyError($code));

        $client->getChannelMessage('245963893292923965', '737645596567095093');
    }

    /**
     * @dataProvider provideInvalidChannelMessage
     * @param $message
     * @param $channel
     * @throws TransportExceptionInterface
     */
    public function testGetChannelMessageBadChannelArgument($message, $channel)
    {
        $this->expectException(InvalidArgumentException::class);

        $client = $this->setupClient(MockClient::emptyBadRequest());

        $client->getChannelMessage($message, $channel);
    }
}