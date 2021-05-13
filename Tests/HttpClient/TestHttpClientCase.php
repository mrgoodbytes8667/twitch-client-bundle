<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient;

use Bytes\Common\Faker\Providers\MiscProvider;
use Bytes\Common\Faker\Providers\Twitch;
use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
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
    use TestFullValidatorTrait, FullSerializerTrait, TestTwitchFakerTrait;

//    /**
//     * @var SerializerInterface
//     */
//    protected $serializer;

    /**
     * @param ResponseInterface|ClientResponseInterface $response
     * @param string $message
     * @throws TransportExceptionInterface
     *
     * @deprecated Since 0.0.1, use "\Bytes\ResponseBundle\Test\AssertClientResponseTrait::assertThatForResponse" instead
     */
    public static function assertResponseIsSuccessful(ResponseInterface|ClientResponseInterface $response, string $message = ''): void
    {
        trigger_deprecation('mrgoodbytes8667/twitch-client-bundle', '0.0.1', 'Using "%s" is deprecated, use "%s::%s" instead.', __METHOD__, '\Bytes\ResponseBundle\Test\AssertClientResponseTrait', __FUNCTION__);
        if ($response instanceof ClientResponseInterface) {
            $response = $response->getResponse();
        }
        self::assertThat(
            $response->getStatusCode(),
            self::logicalAnd(
                self::greaterThanOrEqual(200),
                self::lessThan(300)
            ),
            $message
        );
    }

    /**
     * @param ResponseInterface|ClientResponseInterface $response
     * @param int $expectedCode
     * @param string $message
     *
     * @deprecated Since 0.0.1, use "\Bytes\ResponseBundle\Test\AssertClientResponseTrait::assertThatForResponse" instead
     */
    public static function assertResponseStatusCodeSame(ResponseInterface|ClientResponseInterface $response, int $expectedCode, string $message = ''): void
    {
        trigger_deprecation('mrgoodbytes8667/twitch-client-bundle', '0.0.1', 'Using "%s" is deprecated, use "%s::%s" instead.', __METHOD__, '\Bytes\ResponseBundle\Test\AssertClientResponseTrait', __FUNCTION__);
        if ($response instanceof ClientResponseInterface) {
            $response = $response->getResponse();
        }
        self::assertThatForResponse($response, new ResponseStatusCodeSame($expectedCode), $message);
    }

    /**
     * @param ResponseInterface|ClientResponseInterface $response
     * @param Constraint $constraint
     * @param string $message
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @deprecated Since 0.0.1, use "\Bytes\ResponseBundle\Test\AssertClientResponseTrait::assertThatForResponse" instead
     */
    public static function assertThatForResponse(ResponseInterface|ClientResponseInterface $response, Constraint $constraint, string $message = ''): void
    {
        trigger_deprecation('mrgoodbytes8667/twitch-client-bundle', '0.0.1', 'Using "%s" is deprecated, use "%s::%s" instead.', __METHOD__, '\Bytes\ResponseBundle\Test\AssertClientResponseTrait', __FUNCTION__);
        if ($response instanceof ClientResponseInterface) {
            $response = $response->getResponse();
        }
        try {
            self::assertThat($response, $constraint, $message);
        } catch (ExpectationFailedException $exception) {
            $headers = $response->getHeaders(false);
            if (array_key_exists('X-Debug-Exception', $headers) && array_key_exists('X-Debug-Exception-File', $headers)) {
                if (($serverExceptionMessage = $headers['X-Debug-Exception'][0])
                    && ($serverExceptionFile = $headers['X-Debug-Exception-File'][0])) {
                    $serverExceptionFile = explode(':', $serverExceptionFile);
                    $exception->__construct($exception->getMessage(), $exception->getComparisonFailure(), new ErrorException(rawurldecode($serverExceptionMessage), 0, 1, rawurldecode($serverExceptionFile[0]), $serverExceptionFile[1]), $exception->getPrevious());
                }
            }

            throw $exception;
        }
    }

    /**
     * @param ResponseInterface|ClientResponseInterface $response
     * @param int $expectedCode
     * @param string $message
     *
     * @deprecated Since 0.0.1, use "\Bytes\ResponseBundle\Test\AssertClientResponseTrait::assertThatForResponse" instead
     */
    public static function assertResponseStatusCodeNotSame(ResponseInterface|ClientResponseInterface $response, int $expectedCode, string $message = ''): void
    {
        trigger_deprecation('mrgoodbytes8667/twitch-client-bundle', '0.0.1', 'Using "%s" is deprecated, use "%s::%s" instead.', __METHOD__, '\Bytes\ResponseBundle\Test\AssertClientResponseTrait', __FUNCTION__);
        if ($response instanceof ClientResponseInterface) {
            $response = $response->getResponse();
        }
        self::assertThatForResponse($response, static::logicalNot(new ResponseStatusCodeSame($expectedCode)), $message);
    }

    /**
     * @param ResponseInterface|ClientResponseInterface $response
     * @param string $headerName
     * @param string $message
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @deprecated Since 0.0.1, use "\Bytes\ResponseBundle\Test\AssertClientResponseTrait::assertThatForResponse" instead
     */
    public static function assertResponseHasHeader(ResponseInterface|ClientResponseInterface $response, string $headerName, string $message = ''): void
    {
        trigger_deprecation('mrgoodbytes8667/twitch-client-bundle', '0.0.1', 'Using "%s" is deprecated, use "%s::%s" instead.', __METHOD__, '\Bytes\ResponseBundle\Test\AssertClientResponseTrait', __FUNCTION__);
        if ($response instanceof ClientResponseInterface) {
            $response = $response->getResponse();
        }
        self::assertThatForResponse($response, new ResponseConstraint\ResponseHasHeader($headerName), $message);
    }

    /**
     * @param ResponseInterface|ClientResponseInterface $response
     * @param string $message
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @deprecated Since 0.0.1, use "\Bytes\ResponseBundle\Test\AssertClientResponseTrait::assertThatForResponse" instead
     */
    public static function assertResponseHasContent(ResponseInterface|ClientResponseInterface $response, string $message = ''): void
    {
        trigger_deprecation('mrgoodbytes8667/twitch-client-bundle', '0.0.1', 'Using "%s" is deprecated, use "%s::%s" instead.', __METHOD__, '\Bytes\ResponseBundle\Test\AssertClientResponseTrait', __FUNCTION__);
        if ($response instanceof ClientResponseInterface) {
            $response = $response->getResponse();
        }
        static::assertThat($response->getContent(false), static::logicalNot(static::isEmpty()), $message);
    }

    /**
     * @param ResponseInterface|ClientResponseInterface $response
     * @param string $message
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @deprecated Since 0.0.1, use "\Bytes\ResponseBundle\Test\AssertClientResponseTrait::assertThatForResponse" instead
     */
    public static function assertResponseHasNoContent(ResponseInterface|ClientResponseInterface $response, string $message = ''): void
    {
        trigger_deprecation('mrgoodbytes8667/twitch-client-bundle', '0.0.1', 'Using "%s" is deprecated, use "%s::%s" instead.', __METHOD__, '\Bytes\ResponseBundle\Test\AssertClientResponseTrait', __FUNCTION__);
        if ($response instanceof ClientResponseInterface) {
            $response = $response->getResponse();
        }
        static::assertThat($response->getContent(false), static::logicalAnd(static::isEmpty()), $message);
    }

    /**
     * @param ResponseInterface|ClientResponseInterface $response
     * @param string $content
     * @param string $message
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     *
     * @deprecated Since 0.0.1, use "\Bytes\ResponseBundle\Test\AssertClientResponseTrait::assertThatForResponse" instead
     */
    public static function assertResponseContentSame(ResponseInterface|ClientResponseInterface $response, string $content, string $message = ''): void
    {
        trigger_deprecation('mrgoodbytes8667/twitch-client-bundle', '0.0.1', 'Using "%s" is deprecated, use "%s::%s" instead.', __METHOD__, '\Bytes\ResponseBundle\Test\AssertClientResponseTrait', __FUNCTION__);

        if ($response instanceof ClientResponseInterface) {
            $response = $response->getResponse();
        }
        self::assertThatForResponse($response, new ResponseContentSame($content), $message);
    }

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