<?php

namespace Bytes\TwitchClientBundle\Tests\Controller\ArgumentResolver;

use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\Tests\Common\ClientExceptionResponseProviderTrait;
use Bytes\Tests\Common\DataProvider\BooleanProviderTrait;
use Bytes\Tests\Common\TestArgumentMetadataTrait;
use Bytes\TwitchClientBundle\Attribute\MapTwitchGame;
use Bytes\TwitchClientBundle\Controller\ArgumentResolver\TwitchGameValueResolver;
use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient\TestTwitchClientCase;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchResponseBundle\Objects\Games\Game;
use Bytes\TwitchResponseBundle\Objects\Interfaces\GameInterface;
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
class TwitchGameValueResolverTest extends TestTwitchClientCase
{
    use TestArgumentMetadataTrait, TestTwitchFakerTrait, BooleanProviderTrait, ClientExceptionResponseProviderTrait;

    /**
     * @return Generator
     */
    public static function provideMapTwitchGame(): Generator
    {
        foreach (self::provideUserStreamClasses() as $generator) {
            foreach ([true, false] as $provideBoolean) {
                foreach ([true, false] as $provideBoolean2) {
                    $className = u($generator['class'])->afterLast("\\")->append('::');
                    yield u('game')->append(' ', 'ea consequatur nihil ', $provideBoolean ? 'true' : 'false', $provideBoolean2 ? 'true' : 'false')->snake()->prepend($className)->toString() => ['class' => $generator['class'], 'mockFile' => $generator['mockFile'], 'argName' => 'game', 'argValue' => 'ea consequatur nihil', 'useName' => $provideBoolean, 'useIgdbId' => $provideBoolean2];
                    yield u('id')->append(' ', '953931171574 ', $provideBoolean ? 'true' : 'false', $provideBoolean2 ? 'true' : 'false')->snake()->prepend($className)->toString() => ['class' => $generator['class'], 'mockFile' => $generator['mockFile'], 'argName' => 'id', 'argValue' => '953931171574', 'useName' => $provideBoolean, 'useIgdbId' => $provideBoolean2];
                }
            }
        }
    }

    public static function provideUserStreamClasses(): Generator
    {
        yield 'game' => ['class' => Game::class, 'mockFile' => 'HttpClient/get-game-success.json'];
    }

    /**
     * @dataProvider provideUserStreamClasses
     * @param class-string<GameInterface> $class
     * @param string $mockFile
     * @return void
     * @throws Exception
     */
    public function testApply($class, $mockFile)
    {
        $request = new Request([], [], ['game' => 'ea consequatur nihil']);
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture($mockFile),
        ]));
        $converter = new TwitchGameValueResolver($client);

        $config = $this->createArgumentMetadata($class, 'game');

        $objects = (array)$converter->resolve($request, $config);
        self::assertCount(1, $objects);
        $object = array_shift($objects);
        self::assertEquals('ea consequatur nihil', $object->getName());
    }

    /**
     * @dataProvider provideMapTwitchGame
     * @param class-string<GameInterface> $class
     * @param string $mockFile
     * @param string $argName
     * @param string $argValue
     * @param bool $useName
     * @param bool $useIgdbId
     * @return void
     * @throws Exception
     */
    public function testApplyWithMapTwitch($class, $mockFile, $argName, $argValue, $useName, $useIgdbId)
    {
        $request = new Request([], [], [$argName => $argValue]);
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture($mockFile),
        ]));
        $converter = new TwitchGameValueResolver($client);

        $config = $this->createArgumentMetadata($class, $argName, false, [new MapTwitchGame(useIgdbId: $useIgdbId, useName: $useName)]);

        $objects = (array)$converter->resolve($request, $config);
        self::assertCount(1, $objects);
        $object = array_shift($objects);
        self::assertEquals('ea consequatur nihil', $object->getName());
    }

    /**
     *
     */
    public function testSupports()
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/get-game-success.json'),
        ]));
        $converter = new TwitchGameValueResolver($client);

        $config = $this->createArgumentMetadata(Game::class, $this->faker->userName());
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
            MockJsonResponse::makeFixture('HttpClient/get-game-success.json'),
        ]));
        $converter = new TwitchGameValueResolver($client);

        $config = $this->createArgumentMetadata(Game::class, 'game', true);

        self::assertEmpty($converter->resolve($request, $config));
    }

    /**
     * This shouldn't be possible based on supports but it should return an empty array if it does occur
     */
    public function testApplyBadClass()
    {
        $client = $this->setupClient(new MockHttpClient([
            MockJsonResponse::makeFixture('HttpClient/get-game-success.json'),
        ]));
        $converter = new TwitchGameValueResolver($client);

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
        $request = new Request([], [], ['game' => 'ea consequatur nihil']);
        $client = $this->setupClient(MockClient::emptyBadRequest());
        $converter = new TwitchGameValueResolver($client);

        $config = $this->createArgumentMetadata(Game::class, 'game');

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
        $request = new Request([], [], ['game' => 'ea consequatur nihil']);
        $client = $this->setupClient(MockClient::emptyError($code));
        $converter = new TwitchGameValueResolver($client);

        $config = $this->createArgumentMetadata(Game::class, 'game');

        self::expectException(NotFoundHttpException::class);
        $converter->resolve($request, $config);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testApplyNullValue()
    {
        $request = new Request([], [], ['game' => null]);
        $converter = new TwitchGameValueResolver($this->setupClient(MockClient::empty()));

        $config = $this->createArgumentMetadata(Game::class, 'game');

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
        $request = new Request([], [], ['game' => new DateTime()]);
        $converter = new TwitchGameValueResolver($this->setupClient(MockClient::empty()));

        $config = $this->createArgumentMetadata(Game::class, 'game');

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
        $converter = new TwitchGameValueResolver($this->setupClient(MockClient::empty()));

        $config = $this->createArgumentMetadata(Game::class, 'game');

        self::assertEmpty($converter->resolve($request, $config));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testApplyBadValue()
    {
        $user = new Game();
        $user->setName('ea consequatur nihil');
        
        $request = new Request([], [], ['game' => $user]);
        $converter = new TwitchGameValueResolver($this->setupClient(MockClient::empty()));

        $config = $this->createArgumentMetadata(Game::class, 'game');

        $objects = (array)$converter->resolve($request, $config);
        self::assertCount(1, $objects);
        $object = array_shift($objects);
        self::assertEquals('ea consequatur nihil', $object->getName());
    }
}
