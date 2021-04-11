<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient;


use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;

/**
 * Trait ReactionsProviderTrait
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient
 */
trait ReactionsProviderTrait
{
    use MessageProviderTrait;

    /**
     * @return \Generator
     */
    public function provideValidCreateReaction()
    {
        $this->setupFaker();

        foreach($this->provideValidDeleteMessages() as $message) {
            yield ['message' => $message['message'], 'channel' => $message['channel'], 'emoji' => $this->faker->globalEmoji()];
            yield ['message' => $message['message'], 'channel' => $message['channel'], 'emoji' => $this->faker->customEmoji()];
            yield ['message' => $message['message'], 'channel' => $message['channel'], 'emoji' => $this->faker->emoji()];
        }
    }

}