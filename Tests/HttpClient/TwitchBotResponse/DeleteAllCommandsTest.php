<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse;

use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient\TestTwitchBotClientCase;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class DeleteAllCommandsTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse
 */
class DeleteAllCommandsTest extends TestTwitchBotClientCase
{
    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testDeleteAllCommands()
    {
        $commands = $this
            ->setupResponse(content: '[]', type: '\Bytes\TwitchResponseBundle\Objects\Slash\ApplicationCommand[]')
            ->deserialize();

        $this->assertCount(0, $commands);
    }
}