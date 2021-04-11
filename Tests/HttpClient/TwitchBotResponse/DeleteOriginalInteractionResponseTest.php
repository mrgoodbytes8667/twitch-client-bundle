<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse;

use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient\TestTwitchBotClientCase;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestEmptyResponseTrait;

/**
 * Class DeleteOriginalInteractionResponseTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse
 */
class DeleteOriginalInteractionResponseTest extends TestTwitchBotClientCase
{
    use TestEmptyResponseTrait {
        TestEmptyResponseTrait::testSuccess as testDeleteOriginalInteractionResponse;
        TestEmptyResponseTrait::testSuccessInvalidReturnCode as testDeleteOriginalInteractionResponseInvalidReturnCode;
    }
}