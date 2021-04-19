<?php


namespace Bytes\TwitchClientBundle\Tests\MockHttpClient;


use Bytes\Common\Faker\Providers\Discord;
use Bytes\Common\Faker\Providers\MiscProvider;
use Bytes\Common\Faker\Providers\Twitch;
use Bytes\Tests\Common\MockHttpClient\MockResponseHeaderInterface;
use Faker\Factory;
use Faker\Generator as FakerGenerator;

/**
 * Class MockDiscordResponseHeader
 * @package Bytes\TwitchClientBundle\Tests\MockHttpClient
 */
class MockTwitchResponseHeader implements MockResponseHeaderInterface
{
    /**
     * @inheritDoc
     */
    public function getRateLimitArray(): array
    {
        /** @var FakerGenerator|Twitch $faker */
        $faker = Factory::create();
        $faker->addProvider(new MiscProvider($faker));
        $faker->addProvider(new Twitch($faker));

        return $faker->rateLimitArray();
    }
}