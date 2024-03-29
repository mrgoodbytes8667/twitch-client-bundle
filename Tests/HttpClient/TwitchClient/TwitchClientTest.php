<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;

use Bytes\ResponseBundle\Enums\TokenSource;
use Bytes\ResponseBundle\Test\AssertClientAnnotationsSameTrait;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestTwitchClientTrait;

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
        $this->assertClientAnnotationEquals('TWITCH', TokenSource::app, $client);
    }

    /**
     *
     */
    public function testUsesClientAnnotations()
    {
        $this->assertUsesClientAnnotations($this->setupRealClient());
    }
}
