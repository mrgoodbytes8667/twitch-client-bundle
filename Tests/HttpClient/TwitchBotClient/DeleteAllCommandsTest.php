<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient;

use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class DeleteAllCommandsTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient
 */
class DeleteAllCommandsTest extends TestTwitchBotClientCase
{
    /**
     * @throws TransportExceptionInterface
     */
    public function testDeleteAllCommands()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::make('[]')));

        $cmd = $client->deleteAllCommands();
        $this->assertResponseIsSuccessful($cmd);
        $this->assertResponseStatusCodeSame($cmd, Response::HTTP_OK);
    }
}