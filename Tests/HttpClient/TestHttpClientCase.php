<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient;

use Bytes\Common\Faker\Providers\MiscProvider;
use Bytes\Common\Faker\Providers\Twitch;
use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Bytes\ResponseBundle\Test\AssertClientResponseTrait;
use Bytes\Tests\Common\Constraint\ResponseContentSame;
use Bytes\Tests\Common\Constraint\ResponseStatusCodeSame;
use Bytes\Tests\Common\TestFullValidatorTrait;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchClientBundle\Tests\FullSerializerTrait;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockStandaloneResponse;
use Bytes\TwitchResponseBundle\Objects\Interfaces\UserInterface;
use Bytes\TwitchResponseBundle\Objects\Users\User;
use ErrorException;
use Exception;
use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Internet;
use InvalidArgumentException;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Response as Http;
use Symfony\Component\HttpFoundation\Test\Constraint as ResponseConstraint;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Class TestHttpClientCase
 * @package Bytes\TwitchClientBundle\Tests\HttpClient
 */
abstract class TestHttpClientCase extends TestCase
{
    use AssertClientResponseTrait, TestFullValidatorTrait, FullSerializerTrait, TestTwitchFakerTrait;

    /**
     * @param bool $expected
     * @param $actual
     * @param string $message
     */
    public static function assertShouldBeNull($expected, $actual, string $message = ''): void
    {
        if ($expected === true) {
            static::assertNull($actual, $message);
        } elseif ($expected === false) {
            static::assertNotNull($actual, $message);
        } else {
            throw new InvalidArgumentException('Expected should be a boolean');
        }
    }

    /**
     * @return string
     */
    protected static function getRandomEmoji()
    {
        return self::getFaker()->emoji();
    }

    /**
     * @return Twitch|Generator|MiscProvider
     */
    private static function getFaker()
    {
        /** @var Generator|Twitch $faker */
        $faker = Factory::create();
        $faker->addProvider(new Twitch($faker));

        return $faker;
    }

    /**
     * @param string|null $fixtureFile
     * @param null $content
     * @param int $code
     * @param string $type
     * @return ClientResponseInterface
     */
    public function setupResponse(?string $fixtureFile = null, $content = null, int $code = Http::HTTP_OK, $type = stdClass::class): ClientResponseInterface
    {
        $response = new MockStandaloneResponse(content: $content, fixtureFile: $fixtureFile, statusCode: $code);

        return TwitchResponse::make($this->serializer)->withResponse($response, $type);
    }

    /**
     * @return \Generator
     */
    public function provideBooleans()
    {
        yield [true];
        yield [false];
    }

    /**
     * @return \Generator
     */
    public function provideBooleansAndNull()
    {
        yield [true];
        yield [false];
        yield [null];
    }

    /**
     * @param string|null $userId
     * @param string|null $login
     * @param string|null $broadcasterType = ['partner', 'affiliate', ''][$any]
     * @param string|null $description
     * @param string|null $displayName
     * @param string|null $email
     * @param string|null $offlineImageUrl
     * @param string|null $profileImageUrl
     * @param string|null $type = ['staff', 'admin', 'global_mod', ''][$any]
     * @param int|null $viewCount
     * @return UserInterface
     * @throws Exception
     */
    public function createMockUser(?string $userId = null, ?string $login = null, ?string $broadcasterType = null, ?string $description = null, ?string $displayName = null, ?string $email = null, ?string $offlineImageUrl = null, ?string $profileImageUrl = null, ?string $type = null, ?int $viewCount = null)
    {
        if (empty($userId) && empty($login) && empty($broadcasterType) && empty($description) && empty($displayName) && empty($email) && empty($offlineImageUrl) && empty($profileImageUrl) && empty($type) && is_null($viewCount)) {
            /** @var Generator|Twitch|MiscProvider|Internet $faker */
            $faker = Factory::create();
            $faker->addProvider(new Twitch($faker));

            $userId = $faker->id();
            $login = $this->faker->userName();
            $broadcasterType = $faker->optional()->randomElement(['partner', 'affiliate', '']);
            $description = $faker->optional()->paragraph();
            $displayName = $faker->optional()->passthrough($login);
            $email = $faker->optional()->email();
            $offlineImageUrl = $faker->optional()->imageUrl();
            $profileImageUrl = $faker->optional()->imageUrl();
            $type = $faker->optional()->randomElement(['staff', 'admin', 'global_mod', '']);
            $viewCount = $faker->optional()->numberBetween();
        }
        return User::make($userId, $login, $broadcasterType, $description, $displayName, $email, $offlineImageUrl, $profileImageUrl, $type, $viewCount);
    }

    /**
     * @param HttpClientInterface $httpClient
     * @return mixed
     */
    abstract protected function setupClient(HttpClientInterface $httpClient);
}