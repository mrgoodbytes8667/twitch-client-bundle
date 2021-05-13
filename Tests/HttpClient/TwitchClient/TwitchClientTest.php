<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;

use Bytes\ResponseBundle\Enums\TokenSource;
use Bytes\ResponseBundle\HttpClient\Retry\APIRetryStrategy;
use Bytes\ResponseBundle\Test\AssertClientAnnotationsSameTrait;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestTwitchClientTrait;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Class TwitchClientTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
class TwitchClientTest extends TestTwitchClientCase
{
    use AssertClientAnnotationsSameTrait, TestTwitchClientTrait;

    /**
     *
     */
    public function testClientAnnotations()
    {
        $client = $this->setupRealClient();
        $this->assertClientAnnotationEquals('TWITCH', TokenSource::app(), $client);
    }

    /**
     *
     */
    public function testUsesClientAnnotations()
    {
        $this->assertUsesClientAnnotations($this->setupRealClient());
    }
}
