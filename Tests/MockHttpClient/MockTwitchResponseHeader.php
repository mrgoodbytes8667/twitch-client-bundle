<?php


namespace Bytes\TwitchClientBundle\Tests\MockHttpClient;


use Bytes\Common\Faker\Providers\MiscProvider;
use Bytes\Common\Faker\Providers\Twitch;
use Bytes\Tests\Common\MockHttpClient\MockResponseHeaderInterface;
use Faker\Factory;
use Faker\Generator as FakerGenerator;
use JetBrains\PhpStorm\Pure;

/**
 * Class MockDiscordResponseHeader
 * @package Bytes\TwitchClientBundle\Tests\MockHttpClient
 */
class MockTwitchResponseHeader implements MockResponseHeaderInterface
{
    /**
     * @return static
     */
    #[Pure] public static function create(): static
    {
        return new static();
    }

    /**
     * @inheritDoc
     */
    public function getRateLimitArray(): array
    {
        return static::getFaker()->rateLimitArray();
    }

    /**
     * @return Twitch|FakerGenerator
     */
    public static function getFaker()
    {
        /** @var FakerGenerator|Twitch $faker */
        $faker = Factory::create();
        $faker->addProvider(new MiscProvider($faker));
        $faker->addProvider(new Twitch($faker));

        return $faker;
    }
}