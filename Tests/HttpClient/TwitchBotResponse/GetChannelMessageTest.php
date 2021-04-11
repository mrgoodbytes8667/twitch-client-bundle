<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse;

use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient\TestTwitchBotClientCase;
use Bytes\TwitchResponseBundle\Objects\Message;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class GetChannelMessageTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse
 */
class GetChannelMessageTest extends TestTwitchBotClientCase
{
    use TestValidateChannelMessageTrait;

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testGetChannelMessage()
    {
        $message = $this
            ->setupResponse('HttpClient/get-channel-message-success.json', type: Message::class)
            ->deserialize();

        $this->validateChannelMessage($message, '234627215184254300', 19,
            'Repudiandae voluptas et eveniet hic omnis omnis.', '239800192314657438',
            '247310322447430678', 'rhea17', 'a_24f13635d91a054a52938745fc037761',
            '7461', 1, null, 0, 0, true,
            true, true, true);
    }
}