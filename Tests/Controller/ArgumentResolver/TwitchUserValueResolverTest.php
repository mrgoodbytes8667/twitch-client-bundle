<?php

namespace Bytes\TwitchClientBundle\Tests\Controller\ArgumentResolver;

use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\Tests\Common\ClientExceptionResponseProviderTrait;
use Bytes\Tests\Common\DataProvider\BooleanProviderTrait;
use Bytes\Tests\Common\TestArgumentMetadataTrait;
use Bytes\TwitchClientBundle\Attribute\MapTwitchName;
use Bytes\TwitchClientBundle\Controller\ArgumentResolver\TwitchUserValueResolver;
use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient\TestTwitchClientCase;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchResponseBundle\Objects\Interfaces\TwitchUserInterface;
use Bytes\TwitchResponseBundle\Objects\Streams\Stream;
use Bytes\TwitchResponseBundle\Objects\Users\User;
use DateTime;
use Exception;
use Generator;
use LogicException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function Symfony\Component\String\u;

/**
 *
 */
class TwitchUserValueResolverTest extends TestTwitchClientCase
{
    use TestArgumentMetadataTrait, TestTwitchFakerTrait, BooleanProviderTrait, ClientExceptionResponseProviderTrait;

    /**
     * @return Generator
     */
    public static function provideMapTwitchUser(): Generator
    {
        foreach (self::provideUserStreamClasses() as $generator) {
            foreach ([true, false] as $provideBoolean) {
                $className = u($generator['class'])->afterLast("\\")->append('::');
                yield u('user')->append(' ', 'Mjohns ', $provideBoolean ? 'true' : 'false')->snake()->prepend($className)->toString() => ['class' => $generator['class'], 'mockFile' => $generator['mockFile'], 'argName' => 'user', 'argValue' => 'Mjohns', 'useLogin' => $provideBoolean];
                yield u('id')->append(' ', '783621262139 ', $provideBoolean ? 'true' : 'false')->snake()->prepend($className)->toString() => ['class' => $generator['class'], 'mockFile' => $generator['mockFile'], 'argName' => 'id', 'argValue' => '783621262139', 'useLogin' => $provideBoolean];
                yield u('userName')->append(' ', 'Mjohns ', $provideBoolean ? 'true' : 'false')->snake()->prepend($className)->toString() => ['class' => $generator['class'], 'mockFile' => $generator['mockFile'], 'argName' => 'userName', 'argValue' => 'Mjohns', 'useLogin' => $provideBoolean];
                yield u('userId')->append(' ', '783621262139 ', $provideBoolean ? 'true' : 'false')->snake()->prepend($className)->toString() => ['class' => $generator['class'], 'mockFile' => $generator['mockFile'], 'argName' => 'userId', 'argValue' => '783621262139', 'useLogin' => $provideBoolean];
            }
        }
    }

    public static function provideUserStreamClasses(): Generator
    {
        yield 'user' => ['class' => User::class, 'mockFile' => 'HttpClient/get-user-without-email-success.json'];
        yield 'stream' => ['class' => Stream::class, 'mockFile' => 'HttpClient/get-streams-for-value-resolver-success.json'];
    }

    /**
     * @dataProvider provideUserStreamClasses
     * @param class-string<TwitchUserInterface> $class
     * @param string $mockFile
     * @return void
     * @throws Exception
     */
    public function testApply($class, $mockFile)
    {
        $request = new Request([], [], ['user' => 'Mjohns']);
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture($mockFile),
        ]));
        $converter = new TwitchUserValueResolver($client);

        $config = $this->createArgumentMetadata($class, 'user');

        $objects = (array)$converter->resolve($request, $config);
        self::assertCount(1, $objects);
        $object = array_shift($objects);
        self::assertEquals('Mjohns', $object->getUserName());
    }

    /**
     * @dataProvider provideMapTwitchUser
     * @param class-string<TwitchUserInterface> $class
     * @param string $mockFile
     * @param string $argName
     * @param string $argValue
     * @param bool $useLogin
     * @return void
     * @throws Exception
     */
    public function testApplyWithMapTwitch($class, $mockFile, $argName, $argValue, $useLogin)
    {
        $request = new Request([], [], [$argName => $argValue]);
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture($mockFile),
        ]));
        $converter = new TwitchUserValueResolver($client);

        $config = $this->createArgumentMetadata($class, $argName, false, [new MapTwitchName($useLogin)]);

        $objects = (array)$converter->resolve($request, $config);
        self::assertCount(1, $objects);
        $object = array_shift($objects);
        self::assertEquals('Mjohns', $object->getUserName());
    }

    /**
     *
     */
    public function testSupports()
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/get-user-without-email-success.json'),
        ]));
        $converter = new TwitchUserValueResolver($client);

        $config = $this->createArgumentMetadata(User::class, $this->faker->userName());
        self::assertEmpty($converter->resolve(new Request(), $config));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testApplyIsVariadic()
    {
        $request = new Request([], [], []);
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/get-user-without-email-success.json'),
        ]));
        $converter = new TwitchUserValueResolver($client);

        $config = $this->createArgumentMetadata(User::class, 'user', true);

        self::assertEmpty($converter->resolve($request, $config));
    }

    /**
     * This shouldn't be possible based on supports but it should return an empty array if it does occur
     */
    public function testApplyBadClass()
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/get-user-without-email-success.json'),
        ]));
        $converter = new TwitchUserValueResolver($client);

        $request = new Request([], [], ['login' => $this->faker->camelWords()]);
        $config = $this->createArgumentMetadata('DateTime', 'login');

        self::assertEmpty($converter->resolve($request, $config));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testApplyBadRequestError()
    {
        $request = new Request([], [], ['user' => 'Mjohns']);
        $client = $this->setupClient(MockClient::emptyBadRequest());
        $converter = new TwitchUserValueResolver($client);

        $config = $this->createArgumentMetadata(User::class, 'user');

        self::expectException(NotFoundHttpException::class);
        $converter->resolve($request, $config);
    }

    /**
     * @dataProvider provide400Responses
     * @dataProvider provide500Responses
     * @param int $code
     * @return void
     * @throws Exception
     */
    public function testApplyError($code)
    {
        $request = new Request([], [], ['user' => 'Mjohns']);
        $client = $this->setupClient(MockClient::emptyError($code));
        $converter = new TwitchUserValueResolver($client);

        $config = $this->createArgumentMetadata(User::class, 'user');

        self::expectException(NotFoundHttpException::class);
        $converter->resolve($request, $config);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testApplyNullValue()
    {
        $request = new Request([], [], ['user' => null]);
        $converter = new TwitchUserValueResolver($this->setupClient(MockClient::empty()));

        $config = $this->createArgumentMetadata(User::class, 'user');

        $objects = (array)$converter->resolve($request, $config);
        self::assertCount(1, $objects);
        $object = array_shift($objects);
        self::assertNull($object);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testApplyInvalidValueType()
    {
        $request = new Request([], [], ['user' => new DateTime()]);
        $converter = new TwitchUserValueResolver($this->setupClient(MockClient::empty()));

        $config = $this->createArgumentMetadata(User::class, 'user');

        self::expectException(LogicException::class);
        $converter->resolve($request, $config);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testApplyNoValue()
    {
        $request = new Request([], [], []);
        $converter = new TwitchUserValueResolver($this->setupClient(MockClient::empty()));

        $config = $this->createArgumentMetadata(User::class, 'user');

        self::assertEmpty($converter->resolve($request, $config));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testApplyBadValue()
    {
        $user = new User();
        $user->setLogin('mjohns')
            ->setDisplayName('Mjohns');
        $request = new Request([], [], ['user' => $user]);
        $converter = new TwitchUserValueResolver($this->setupClient(MockClient::empty()));

        $config = $this->createArgumentMetadata(User::class, 'user');

        $objects = (array)$converter->resolve($request, $config);
        self::assertCount(1, $objects);
        $object = array_shift($objects);
        self::assertEquals('Mjohns', $object->getUserName());
    }
}
