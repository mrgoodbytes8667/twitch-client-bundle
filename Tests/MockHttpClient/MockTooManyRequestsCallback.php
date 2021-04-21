<?php


namespace Bytes\TwitchClientBundle\Tests\MockHttpClient;


use Bytes\Tests\Common\MockHttpClient\MockClientCallbackIterator;
use Exception;

/**
 * Class MockTooManyRequestsCallback
 * @package Bytes\DiscordBundle\Tests\Command
 */
class MockTooManyRequestsCallback extends MockClientCallbackIterator
{
    /**
     * MockTooManyRequestsCallback constructor.
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->setArray(
            self::getResponses()
        );
    }

    /**
     * @return MockExceededRateLimitResponse[]
     * @throws Exception
     */
    public static function getResponses(float $retryAfter = 0.123)
    {
        return [
            new MockExceededRateLimitResponse($retryAfter),
            new MockExceededRateLimitResponse($retryAfter),
            new MockExceededRateLimitResponse($retryAfter),
            new MockExceededRateLimitResponse($retryAfter),
        ];
    }
}