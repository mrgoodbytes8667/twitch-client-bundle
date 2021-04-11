<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient;

use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchResponseBundle\Enums\JsonErrorCodes;
use Bytes\TwitchResponseBundle\Objects\Embed\Embed;
use Bytes\TwitchResponseBundle\Objects\Message\WebhookContent;
use Generator;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class EditFollowupMessageTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient
 */
class EditFollowupMessageTest extends TestTwitchBotClientCase
{
    use TestTwitchFakerTrait;

    /**
     * @dataProvider provideFollowups
     * @param $token
     * @param $content
     * @param $embeds
     * @param $allowedMentions
     * @param $tts
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testEditFollowupMessage($token, $content, $embeds, $allowedMentions, $tts)
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/edit-followup-message-success.json'),
        ]));

        $response = $client->editFollowupMessage($token, $this->faker->snowflake(), $content, $embeds, $allowedMentions, $tts);

        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getFixturesData('HttpClient/edit-followup-message-success.json'));
    }

    /**
     * @return Generator
     */
    public function provideFollowups()
    {
        $this->setupFaker();

        yield ['token' => $this->faker->accessToken(), 'content' => 'Hello, World!', 'embeds' => Embed::create('Hello, Embed!', description: 'This is an embedded message.'), 'allowedMentions' => null, 'tts' => false];
        yield ['token' => $this->faker->accessToken(), 'content' => WebhookContent::create(Embed::create('Hello, Embed!', description: 'This is an embedded message.'), content: 'Hello, World!', tts: false), 'embeds' => null, 'allowedMentions' => null, 'tts' => false];
    }

    /**
     * @dataProvider provideClientExceptionResponses
     *
     * @param int $code
     */
    public function testCreateFollowupMessageFailure(int $code)
    {
        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('HTTP %d returned for', $code));

        $client = $this->setupClient(MockClient::emptyError($code));
        $client->editFollowupMessage($this->faker->refreshToken(), $this->faker->snowflake(), $this->faker->paragraph());
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testEditFollowupMessageValidationFailure()
    {
        $client = $this->setupClient(MockClient::empty());

        $description = $this->faker->paragraphsMinimumChars(2000);

        $this->expectException(ValidatorException::class);

        $client->editFollowupMessage($this->faker->refreshToken(), $this->faker->snowflake(), WebhookContent::create(content: $description));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testEditFollowupMessageExpiredInteractionToken()
    {
        $client = $this->setupClient(MockClient::jsonErrorCode(JsonErrorCodes::invalidWebhookTokenProvided(), 'Invalid Webhook Token', Response::HTTP_UNAUTHORIZED));

        $response = $client->editFollowupMessage($this->faker->refreshToken(), $this->faker->snowflake(), $this->faker->paragraph());

        $this->assertResponseStatusCodeSame($response, Response::HTTP_UNAUTHORIZED);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getJsonErrorCodeData(JsonErrorCodes::invalidWebhookTokenProvided(), 'Invalid Webhook Token'));

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('HTTP %d returned for', Response::HTTP_UNAUTHORIZED));

        $response->getContent();
    }
}