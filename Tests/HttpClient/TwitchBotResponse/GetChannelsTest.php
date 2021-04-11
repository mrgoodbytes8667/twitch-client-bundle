<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse;

use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient\TestTwitchBotClientCase;
use Bytes\TwitchResponseBundle\Objects\Interfaces\IdInterface;
use Bytes\TwitchResponseBundle\Objects\Overwrite;
use Generator;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class GetChannelsTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse
 */
class GetChannelsTest extends TestTwitchBotClientCase
{
    /**
     * @dataProvider provideValidGuildForGetChannels
     *
     * @param string $file
     * @param $guildId
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetChannels(string $file, $guildId)
    {
        $channels = $this
            ->setupResponse($file, type: '\Bytes\TwitchResponseBundle\Objects\Channel[]')
            ->deserialize();

        $this->assertCount(12, $channels);

        $channel = $channels[0];
        $this->assertEquals('276921226399262614', $channel->getId());
        $this->assertEquals(6, $channel->getType());
        $this->assertEquals('721716783525430558', $channel->getGuildId());
        $this->assertEquals(5, $channel->getPosition());
        $this->assertEquals('Ad sed blanditiis incidunt quae. Et unde optio corporis. Nihil eum ad odio ab.', $channel->getName());

        $this->assertCount(3, $channel->getPermissionOverwrites());
        $overwrite = $channel->getPermissionOverwrites()[0];

        $this->assertInstanceOf(Overwrite::class, $overwrite);
        $this->assertEquals("263397620755496871", $overwrite->getId());
        $this->assertEquals('role', $overwrite->getType());
        $this->assertEquals('6546771529', $overwrite->getAllow());
        $this->assertEquals('6546771529', $overwrite->getDeny());
    }

    /**
     * @return Generator
     */
    public function provideValidGuildForGetChannels()
    {
        $mock = $this
            ->getMockBuilder(IdInterface::class)
            ->getMock();
        $mock->method('getId')
            ->willReturn('230858112993375816');

        yield ['file' => 'HttpClient/get-channels-v8-success.json', 'guildId' => '230858112993375816'];
        yield ['file' => 'HttpClient/get-channels-v8-success.json', 'guildId' => $mock];
    }
}
