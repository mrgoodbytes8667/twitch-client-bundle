<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse;

use Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotClient\TestTwitchBotClientCase;
use Bytes\TwitchClientBundle\Tests\HttpClient\TestEmptyResponseTrait;

/**
 * Class CreateReactionTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient\TwitchBotResponse
 */
class CreateReactionTest extends TestTwitchBotClientCase
{
    use TestEmptyResponseTrait {
        TestEmptyResponseTrait::testSuccess as testCreateReaction;
        TestEmptyResponseTrait::testSuccessInvalidReturnCode as testCreateReactionInvalidReturnCode;
    }
}
