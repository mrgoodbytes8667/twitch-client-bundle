<?php

namespace Bytes\TwitchClientBundle\Tests\Request;

use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchClientBundle\Request\TwitchUserConverter;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockJsonResponse;
use Bytes\TwitchClientBundle\Tests\TwitchClientSetupTrait;
use Bytes\TwitchResponseBundle\Objects\Users\User;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class TwitchUserConverterTest extends TestParamConverterCase
{
    use TwitchClientSetupTrait {
        TwitchClientSetupTrait::setupBaseClient as setupClient;
    }

    /**
     *
     */
    public function testApply()
    {
        $request = new Request([], [], ['user' => 414076175084]);
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-user-success.json')));
        $converter = new TwitchUserConverter($client);

        $config = $this->createConfiguration(User::class, 'user');

        $this->assertTrue($converter->apply($request, $config));

        $object = $request->attributes->get('user');
        $this->assertInstanceOf(User::class, $object);
        $this->assertEquals('edd.windler', $object->getLogin());
    }

    /**
     *
     */
    public function testApplyOptional()
    {
        $request = new Request([], [], []);
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-user-success.json')));
        $converter = new TwitchUserConverter($client);

        $config = $this->createConfiguration(User::class, 'user', true);

        $this->assertFalse($converter->apply($request, $config));

        $object = $request->attributes->get('user');
        $this->assertNull($object);
    }

    /**
     *
     */
    public function testSupports()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-user-success.json')));
        $converter = new TwitchUserConverter($client);

        $config = $this->createConfiguration(User::class, 'fudge');
        $this->assertTrue($converter->supports($config));
    }

    /**
     *
     */
    public function testSupportsNoClass()
    {
        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-user-success.json')));
        $converter = new TwitchUserConverter($client);

        $config = $this->createConfiguration();
        $this->assertFalse($converter->supports($config));
    }

    /**
     * This shouldn't be possible based on supports but it should return false if it does occur
     */
    public function testApplyBadParamName()
    {
        $this->setupFaker();

        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-user-success.json')));
        $converter = new TwitchUserConverter($client);

        $request = new Request([], [], [$this->faker->camelWords() => $this->faker->camelWords()]);
        $config = $this->createConfiguration(User::class, 'some_fake_parm');

        $this->assertFalse($converter->apply($request, $config));
    }

    /**
     * This shouldn't be possible based on supports but it should return false if it does occur
     */
    public function testApplyBadClass()
    {
        $this->setupFaker();

        $client = $this->setupClient(MockClient::requests(
            MockJsonResponse::makeFixture('HttpClient/get-user-success.json')));
        $converter = new TwitchUserConverter($client);

        $request = new Request([], [], ['user' => $this->faker->camelWords()]);
        $config = $this->createConfiguration('DateTime', 'user');

        $this->assertFalse($converter->apply($request, $config));
    }

    /**
     *
     */
    public function testApplyApiError()
    {
        $request = new Request([], [], ['user' => 414076175084]);
        $client = $this->setupClient(MockClient::emptyBadRequest());
        $converter = new TwitchUserConverter($client);

        $config = $this->createConfiguration(User::class, 'user');

        $this->assertFalse($converter->apply($request, $config));
    }

    /**
     *
     */
    public function testApplyTransportException()
    {
        $request = new Request([], [], ['user' => 414076175084]);

        $client = $this->getMockBuilder(TwitchClient::class)->disableOriginalConstructor()->getMock();
        $client->method('getUser')->willThrowException(new TransportException());
        $converter = new TwitchUserConverter($client);

        $config = $this->createConfiguration(User::class, 'user');

        $this->assertFalse($converter->apply($request, $config));
    }
}