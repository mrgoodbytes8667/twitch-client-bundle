<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient;

use Bytes\TwitchClientBundle\Tests\TwitchClientSetupTrait;
use Bytes\TwitchClientBundle\Tests\TestUrlGeneratorTrait;

/**
 * Class TwitchAppTokenClientTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient
 */
class TwitchAppTokenClientTest extends TestHttpClientCase
{
    use TestTwitchClientTrait, TestUrlGeneratorTrait, TwitchClientSetupTrait {
        TwitchClientSetupTrait::setupAppTokenClient as setupClient;
    }
}
