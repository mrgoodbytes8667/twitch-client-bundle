<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchUserClient;


use Bytes\TwitchClientBundle\Tests\CommandProviderTrait;
use Bytes\TwitchClientBundle\Tests\TwitchClientSetupTrait;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestHttpClientCase;
use Bytes\TwitchClientBundle\Tests\JsonErrorCodesProviderTrait;

/**
 * Class TestTwitchUserClientCase
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchUserClient
 */
class TestTwitchUserClientCase extends TestHttpClientCase
{
    use CommandProviderTrait, JsonErrorCodesProviderTrait, TwitchClientSetupTrait {
        TwitchClientSetupTrait::setupUserClient as setupClient;
    }
}
