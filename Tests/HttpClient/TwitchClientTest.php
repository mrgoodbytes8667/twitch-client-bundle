<?php

namespace Bytes\TwitchClientBundle\Tests\HttpClient;

use Bytes\TwitchClientBundle\Tests\TwitchClientSetupTrait;
use DateTime;
use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Internet;
use InvalidArgumentException;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TwitchClientTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient
 */
class TwitchClientTest extends TestHttpClientCase
{
    use TestTwitchClientTrait, ExpectDeprecationTrait, TwitchClientSetupTrait {
        TwitchClientSetupTrait::setupBaseClient as setupClient;
    }

    /**
     *
     */
    public function testRequest()
    {
        /** @var Generator|Internet $faker */
        $faker = Factory::create();
        $content = $faker->randomHtml();
        $headers = [
            'X-Lorem-Ipsum' => 'Dolor'
        ];

        $client = $this->setupClient(new MockHttpClient([
            new MockResponse($content, [
                'response_headers' => $headers
            ]),
        ]));

        $response = $client->request($faker->url(), caller: __METHOD__);

        $this->assertResponseIsSuccessful($response);
        $this->assertResponseStatusCodeSame($response, Response::HTTP_OK);
        $this->assertResponseHasContent($response);
        $this->assertResponseContentSame($response, $content);

        $headers = $response->getHeaders();
        $this->assertCount(1, $headers);
        $this->assertArrayHasKey('x-lorem-ipsum', $headers);
        $this->assertCount(1, $headers['x-lorem-ipsum']);
        $this->assertEquals('Dolor', array_shift($headers['x-lorem-ipsum']));
    }

    /**
     * @dataProvider provideInvalidUrls
     * @param $url
     */
    public function testRequestInvalidUrl($url)
    {
        $client = $this->setupClient(new MockHttpClient());

        $this->expectException(InvalidArgumentException::class);
        $client->request($url, caller: __METHOD__);
    }

    /**
     * @return \Generator
     */
    public function provideInvalidUrls()
    {
        yield ['url' => ''];
        yield ['url' => null];
        yield ['url' => []];
        yield ['url' => 1];
        yield ['url' => new DateTime()];
    }
}
