<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient;

use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchResponseBundle\Enums\Emojis;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class CreateReactionTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient
 */
class CreateReactionTest extends TestTwitchBotClientCase
{
    use GuildProviderTrait, ReactionsProviderTrait, TestTwitchFakerTrait;

    /**
     * @dataProvider provideValidCreateReaction
     */
    public function testCreateReaction($message, $channel, $emoji)
    {
        $client = $this->setupClient(MockClient::empty());

        $response = $client->createReaction($message, $emoji, $channel);

        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_NO_CONTENT);
        $this->assertResponseHasNoContent($response);
        $this->assertResponseContentSame($response, '');
    }

    /**
     * @dataProvider provideInvalidGetGuildArguments
     * @param $guild
     * @throws TransportExceptionInterface
     *
     * @todo Clean this up
     */
    public function testCreateReactionBadGuildArgument($guild)
    {
        $this->expectException(InvalidArgumentException::class);

        $client = $this->setupClient(MockClient::emptyBadRequest());

        $client->createReaction($guild, Emojis::sportsGamesHobbiesSoccerBall()->value, $guild);
    }

    /**
     * @dataProvider provideJsonErrorCodes
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testCreateReactionJsonErrorCode($jsonCode, string $message, int $httpCode)
    {
        $client = $this->setupClient(MockClient::jsonErrorCode($jsonCode, $message, $httpCode));

        $response = $client->createReaction('123', self::getRandomEmoji(), '456');

        $this->assertResponseStatusCodeSame($response, $httpCode);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, Fixture::getJsonErrorCodeData($jsonCode, $message));

        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('HTTP %d returned for', $httpCode));

        $response->getContent();
    }

    /**
     * @dataProvider provideClientExceptionResponses
     *
     * @param int $code
     */
    public function testCreateReactionFailure(int $code)
    {
        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('HTTP %d returned for', $code));

        $client = $this->setupClient(MockClient::emptyError($code));

        $client->createReaction('123', self::getRandomEmoji(), '456');
    }
}