<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient;


use Bytes\TwitchClientBundle\Tests\HttpClient\TestHttpClientCase;
use Bytes\TwitchClientBundle\Tests\TwitchClientSetupTrait;

/**
 * Class TestTwitchClientCase
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchClient
 */
class TestTwitchClientCase extends TestHttpClientCase
{
    use TwitchClientSetupTrait {
        TwitchClientSetupTrait::setupBaseClient as setupClient;
    }
}
