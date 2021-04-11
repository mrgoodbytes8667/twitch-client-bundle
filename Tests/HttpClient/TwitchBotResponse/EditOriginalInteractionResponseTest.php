<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse;

use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient\TestTwitchBotClientCase;
use Bytes\TwitchClientBundle\Tests\HttpClient\WebhookProviderTrait;
use Bytes\TwitchResponseBundle\Enums\InteractionType;
use Bytes\TwitchResponseBundle\Enums\MessageType;
use Bytes\TwitchResponseBundle\Objects\Message;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class EditOriginalInteractionResponseTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse
 */
class EditOriginalInteractionResponseTest extends TestTwitchBotClientCase
{
    use WebhookProviderTrait;

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testEditOriginalInteractionResponse()
    {
        /** @var Message $message */
        $message = $this->setupResponse('HttpClient/edit-original-interaction-response-success.json', type: Message::class)->deserialize();

        $this->validateWebhookMessage($message, "487682468505944112", MessageType::default(), "Hello, oh edited World!",
            "246703651155663276", true, "453971306226180868", "Spidey Bot", null, "0000", null, 0, 1, 0, 0, false,
            mentionEveryone: false, tts: false, hasTimestamp: true, hasEditedTimestamp: true, flags: 0, webhookId: "829350728622800916",
            interactionId: '846542216677566910', interactionType: InteractionType::applicationCommand(),
            interactionName: 'sample', interactionUserBot: null, interactionUserId: "272930239796055326",
            interactionUserUsername: 'elvie70', interactionUserAvatar: "cba426068ee1c51edab2f0c38549f4bc",
            interactionUserDiscriminator: '6793', interactionUserFlags: null);
    }
}