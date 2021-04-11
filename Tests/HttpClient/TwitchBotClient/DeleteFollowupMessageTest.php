<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient;

use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchResponseBundle\Enums\JsonErrorCodes;
use Generator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class DeleteFollowupMessageTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient
 */
class DeleteFollowupMessageTest extends TestTwitchBotClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @dataProvider provideDeleteFollowupMessage
     * @param $id
     * @param $token
     * @param $messageId
     * @throws TransportExceptionInterface
     */
    public function testDeleteFollowupMessage($token, $messageId)
    {
        $client = $this->setupClient(MockClient::empty());

        $response = $client->deleteFollowupMessage($token, $messageId);

        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_NO_CONTENT);
        $this->assertResponseHasNoContent($response);
        $this->assertResponseContentSame($response, '');
    }

    /**
     * @dataProvider provideClientExceptionResponses
     *
     * @param int $code
     */
    public function testDeleteFollowupMessageFailure(int $code)
    {
        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('HTTP %d returned for', $code));

        $client = $this->setupClient(MockClient::emptyError($code));

        foreach ($this->provideDeleteFollowupMessage() as $item) {
            $client->deleteFollowupMessage($item['token'], $item['messageId']);
        }
    }

    /**
     * @return Generator
     */
    public function provideDeleteFollowupMessage()
    {
        $this->setupFaker();
        yield ['token' => $this->faker->refreshToken(), 'messageId' => $this->faker->snowflake()];
    }

    /**
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testEditFollowupMessageExpiredInteractionToken()
    {
        $client = $this->setupClient(MockClient::jsonErrorCode(JsonErrorCodes::invalidWebhookTokenProvided(), 'Invalid Webhook Token', Response::HTTP_UNAUTHORIZED));

        $response = $client->deleteFollowupMessage($this->faker->refreshToken(), $this->faker->snowflake());

        $this->assertResponseStatusCodeSame($response, Response::HTTP_UNAUTHORIZED);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getJsonErrorCodeData(JsonErrorCodes::invalidWebhookTokenProvided(), 'Invalid Webhook Token'));

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('HTTP %d returned for', Response::HTTP_UNAUTHORIZED));

        $response->getContent();
    }
}