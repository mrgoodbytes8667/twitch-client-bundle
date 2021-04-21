<?php


namespace Bytes\TwitchClientBundle\Tests\MockHttpClient;


use Bytes\Tests\Common\MockHttpClient\MockResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MockExceededRateLimitResponse
 * @package Bytes\TwitchClientBundle\Tests\MockHttpClient
 */
class MockExceededRateLimitResponse extends MockResponse
{
    /**
     * MockExceededRateLimitResponse constructor.
     * @param string|string[]|iterable $body The response body as a string or an iterable of strings,
     *                                       yielding an empty string simulates an idle timeout,
     *                                       exceptions are turned to TransportException
     * @param array $info = ResponseInterface::getInfo()
     *
     * @see ResponseInterface::getInfo() for possible info, e.g. "response_headers"
     */
    public function __construct($body = '', array $info = [])
    {
        parent::__construct($body, Response::HTTP_TOO_MANY_REQUESTS, $info, MockTwitchExceededRateLimitResponseHeader::create());
    }

    /**
     * @param string $body
     * @param array $info
     * @return static
     */
    public static function make($body = '', array $info = []): static
    {
        return new static($body, $info);
    }
}