<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchResponse;


use Bytes\ResponseBundle\Enums\TokenSource;
use Bytes\ResponseBundle\Test\AssertClientAnnotationsSameTrait;
use Bytes\Tests\Common\TestSerializerTrait;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchAppTokenResponse;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenResponse;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class TwitchTokenResponseTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchResponse
 */
class TwitchTokenResponseTest extends TestCase
{
    use AssertClientAnnotationsSameTrait, TestSerializerTrait;

    /**
     * @dataProvider provideResponse
     * @param $client
     * @param $tokenSource
     */
    public function testClientAnnotations($client, $tokenSource)
    {
        $this->assertClientAnnotationEquals('TWITCH', $tokenSource, $client);
    }

    /**
     * @dataProvider provideResponse
     * @param $client
     * @param $tokenSource
     */
    public function testUsesClientAnnotations($client, $tokenSource)
    {
        $this->assertNotUsesClientAnnotations($client);
    }

    /**
     * @return Generator
     */
    public function provideResponse()
    {
        yield ['client' => new TwitchAppTokenResponse($this->createSerializer(), new EventDispatcher()), 'tokenSource' => TokenSource::app];
        yield ['client' => new TwitchUserTokenResponse($this->createSerializer(), new EventDispatcher()), 'tokenSource' => TokenSource::user];
    }
}