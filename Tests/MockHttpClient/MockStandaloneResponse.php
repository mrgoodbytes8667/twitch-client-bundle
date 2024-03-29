<?php


namespace Bytes\TwitchClientBundle\Tests\MockHttpClient;


use Bytes\Tests\Common\MockHttpClient\MockStandaloneResponse as MockResponse;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MockStandaloneResponse
 * @package Bytes\TwitchClientBundle\Tests\MockHttpClient
 */
class MockStandaloneResponse extends MockResponse
{
    /**
     * MockStandaloneResponse constructor.
     * @param null $content
     * @param string|null $fixtureFile
     * @param int $statusCode
     * @param array $headers
     */
    public function __construct($content = null, ?string $fixtureFile = null, int $statusCode = Response::HTTP_OK, array $headers = [])
    {
        if (empty($content) && !empty($fixtureFile)) {
            $content = function () use ($fixtureFile) {
                return Fixture::getFixturesData($fixtureFile);
            };
        }
        
        parent::__construct($content, $statusCode, $headers);
    }
}