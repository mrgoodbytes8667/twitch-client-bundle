<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse;

use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient\TestTwitchBotClientCase;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestEmptyResponseTrait;

/**
 * Class DeleteMessageTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse
 */
class DeleteMessageTest extends TestTwitchBotClientCase
{
    use TestEmptyResponseTrait {
        TestEmptyResponseTrait::testSuccess as testDeleteMessage;
        TestEmptyResponseTrait::testSuccessInvalidReturnCode as testDeleteMessageInvalidReturnCode;
    }
}