<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse;

use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient\TestTwitchBotClientCase;
use Bytes\TwitchClientBundle\Tests\HttpClient\WebhookProviderTrait;
use Bytes\TwitchResponseBundle\Enums\MessageType;
use Bytes\TwitchResponseBundle\Objects\Message;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class EditFollowupMessageTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse
 */
class EditFollowupMessageTest extends TestTwitchBotClientCase
{
    use WebhookProviderTrait;

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testEditFollowupMessage()
    {
        /** @var Message $message */
        $message = $this->setupResponse('HttpClient/edit-followup-message-success.json', type: Message::class)->deserialize();

        $this->validateWebhookMessage($message, "487682468505944112", MessageType::default(), "Hello, World!",
            "246703651155663276", true, "453971306226180868", "Spidey Bot", null, "0000", null, 0, 1, 0, 0, false,
            mentionEveryone: false, tts: false, hasTimestamp: true, hasEditedTimestamp: true, flags: 0, webhookId: "829350728622800916",
            msgRefChannelId: "246703651155663276", msgRefGuildId: '868040621832012725', msgRefMessageId: '834001394719433221');
    }
}