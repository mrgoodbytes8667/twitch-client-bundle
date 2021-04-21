<?php


namespace Bytes\TwitchClientBundle\Tests\MockHttpClient;


use Bytes\Tests\Common\MockHttpClient\MockClient as BaseMockClient;
use Exception;
use Symfony\Component\HttpClient\MockHttpClient;

/**
 * Class MockClient
 * @package Bytes\TwitchClientBundle\Tests\MockHttpClient
 */
class MockClient extends BaseMockClient
{
    /**
     * @return MockHttpClient
     */
    public static function emptyData()
    {
        return static::client(new MockJsonResponse(body: '{"data": []}'));
    }

    /**
     * @return MockHttpClient
     * @throws Exception
     */
    public static function rateLimit(float $retryAfter = 0.123)
    {
        return static::client(MockTooManyRequestsCallback::getResponses($retryAfter));
    }
}