<?php


namespace Bytes\TwitchClientBundle\Tests\MockHttpClient;


use Bytes\Common\Faker\Providers\Twitch;
use Bytes\Common\Faker\Providers\MiscProvider;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MockTwitchResponse
 * @package Bytes\TwitchClientBundle\Tests\MockHttpClient
 */
class MockTwitchResponse extends MockResponse
{
    /**
     * MockTwitchResponse constructor.
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
        /** @var FakerGenerator|Twitch $faker */
        $faker = Factory::create();
        $faker->addProvider(new MiscProvider($faker));
        $faker->addProvider(new Twitch($faker));

        $info = self::normalizeInfo($info);
        $info['response_headers'] = array_merge($faker->rateLimitArray(), $info['response_headers']);

        if(!array_key_exists('http_code', $info)) {
            $info['http_code'] = $code;
        }
        parent::__construct($body, $info);
    }

    /**
     * Plug in response headers key if not exists
     * @param array $info
     * @return array
     */
    protected static function normalizeInfo(array $info = [])
    {
        if(!array_key_exists('response_headers', $info)) {
            $info['response_headers'] = [];
        }

        return $info;
    }
}