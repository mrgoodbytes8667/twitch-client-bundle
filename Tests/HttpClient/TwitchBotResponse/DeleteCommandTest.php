<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse;

use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient\TestTwitchBotClientCase;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestEmptyResponseTrait;

/**
 * Class DeleteCommandTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse
 */
class DeleteCommandTest extends TestTwitchBotClientCase
{
    use TestEmptyResponseTrait {
        TestEmptyResponseTrait::testSuccess as testDeleteCommand;
        TestEmptyResponseTrait::testSuccessInvalidReturnCode as testDeleteCommandInvalidReturnCode;
    }
}
