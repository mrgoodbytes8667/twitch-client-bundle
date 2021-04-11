<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient;


use Bytes\TwitchClientBundle\Tests\CommandProviderTrait;
use Bytes\TwitchClientBundle\Tests\TwitchClientSetupTrait;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestHttpClientCase;
use Bytes\TwitchClientBundle\Tests\JsonErrorCodesProviderTrait;

/**
 * Class TestTwitchBotClientCase
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient
 */
class TestTwitchBotClientCase extends TestHttpClientCase
{
    use CommandProviderTrait, JsonErrorCodesProviderTrait, TwitchClientSetupTrait {
        TwitchClientSetupTrait::setupBotClient as setupClient;
    }
}
