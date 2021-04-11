<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient;

use Bytes\TwitchClientBundle\Tests\TwitchClientSetupTrait;
use Bytes\TwitchClientBundle\Tests\TestUrlGeneratorTrait;

/**
 * Class TwitchTokenClientTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient
 *
 * @requires PHPUnit >= 9
 */
class TwitchTokenClientTest extends TestHttpClientCase
{
    use TestTwitchClientTrait, TestUrlGeneratorTrait, TwitchClientSetupTrait {
        TwitchClientSetupTrait::setupTokenClient as setupClient;
    }
}
