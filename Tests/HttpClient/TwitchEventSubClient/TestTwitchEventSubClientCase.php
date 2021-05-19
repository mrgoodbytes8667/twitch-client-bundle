<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchEventSubClient;


use Bytes\TwitchClientBundle\Tests\HttpClient\TestHttpClientCase;
use Bytes\TwitchClientBundle\Tests\TwitchClientSetupTrait;

/**
 * Class TestTwitchEventSubClientCase
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
class TestTwitchEventSubClientCase extends TestHttpClientCase
{
    use TwitchClientSetupTrait {
        TwitchClientSetupTrait::setupEventSubClient as setupClient;
    }
}
