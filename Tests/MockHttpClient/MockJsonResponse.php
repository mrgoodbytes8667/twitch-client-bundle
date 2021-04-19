<?php


namespace Bytes\TwitchClientBundle\Tests\MockHttpClient;


use Bytes\Tests\Common\MockHttpClient\MockResponse;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MockJsonResponse
 * @package Bytes\TwitchClientBundle\Tests\MockHttpClient
 */
class MockJsonResponse extends MockResponse
{
    /**
     * MockJsonResponse constructor.
     * @param string|string[]|iterable $body The response body as a string or an iterable of strings,
     *                                       yielding an empty string simulates an idle timeout,
     *                                       exceptions are turned to TransportException
     * @param int $code
     * @param array $info = ResponseInterface::getInfo()
     *
     * @see ResponseInterface::getInfo() for possible info, e.g. "response_headers"
     */
    public function __construct($body = '', int $code = Response::HTTP_OK, array $info = [])
    {
        $info['response_headers']['Content-Type'] = 'application/json';
        parent::__construct($body, $code, $info, new MockTwitchResponseHeader());
    }

    /**
     * @param string $file
     * @param int $code
     * @param array $info
     * @return static
     */
    public static function makeFixture(string $file, int $code = Response::HTTP_OK, array $info = []) {
        return new static(Fixture::getFixturesData($file), $code, $info);
    }

    /**
     * @param string|array $data
     * @param int $code
     * @param array $info
     * @return static
     */
    public static function make($data, int $code = Response::HTTP_OK, array $info = []) {
        return new static(json_encode($data), $code, $info);
    }
}