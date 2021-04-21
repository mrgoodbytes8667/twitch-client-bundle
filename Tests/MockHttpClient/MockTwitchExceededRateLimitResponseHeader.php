<?php


namespace Bytes\TwitchClientBundle\Tests\MockHttpClient;


/**
 * Class MockTwitchExceededRateLimitResponseHeader
 * @package Bytes\TwitchClientBundle\Tests\MockHttpClient
 */
class MockTwitchExceededRateLimitResponseHeader extends MockTwitchResponseHeader
{
    /**
     * @inheritDoc
     */
    public function getRateLimitArray(): array
    {
        return static::getFaker()->rateLimitArray(true);
    }
}