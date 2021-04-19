<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient;


use Bytes\Common\Faker\Providers\Twitch;
use Bytes\Common\Faker\Providers\MiscProvider;
use Bytes\ResponseBundle\HttpClient\Response\Response;
use Bytes\Tests\Common\ClientExceptionResponseProviderTrait;
use Bytes\TwitchClientBundle\HttpClient\TwitchClient;
use Bytes\TwitchClientBundle\Tests\TwitchClientSetupTrait;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Faker\Factory;
use Generator;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response as Http;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function Symfony\Component\String\u;

/**
 * Class TwitchResponseTest
 * @package Bytes\TwitchClientBundle\Tests\HttpClient
 */
class TwitchResponseTest extends TestHttpClientCase
{
    use ClientExceptionResponseProviderTrait, TwitchClientSetupTrait {
        TwitchClientSetupTrait::setupBaseClient as setupClient;
    }

//    /**
//     * @throws TransportExceptionInterface
//     */
//    public function testRequestTimeout()
//    {
//        $body = function () {
//            // empty strings are turned into timeouts so that they are easy to test
//            yield '';
//            yield '';
//            yield '';
//            yield '';
//            yield '';
//            yield '';
//            yield '';
//            yield '';
//        };
//
//        $client = $this->setupClient(new MockHttpClient([
//            new MockResponse($body()),
//        ]), [
//            // Matches non-oauth API routes
//            TwitchClient::SCOPE_API => [
//                'headers' => ['User-Agent' => Fixture::USER_AGENT],
//                'timeout' => 2.5,
//            ]
//        ]);
//
//        $this->expectException(TransportExceptionInterface::class);
//
//        $client->getMe()->isSuccess();
//    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testIsSuccessWithException()
    {
        $response = $this
            ->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $response->method('getStatusCode')
            ->willThrowException(new TransportException());

        $twitchResponse = Response::make($this->serializer)->withResponse($response, null);

        $this->expectException(TransportException::class);
        $twitchResponse->getStatusCode();
    }

    /**
     * @dataProvider provide200Responses
     * @param $code
     * @param $success
     */
    public function testIsSuccess($code, $success)
    {
        $response = $this
            ->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $response->method('getStatusCode')
            ->willReturn($code);

        $twitchResponse = Response::make($this->serializer)->withResponse($response, null);

        $this->assertTrue($twitchResponse->isSuccess());
    }

    /**
     * @dataProvider provide100Responses
     * @dataProvider provide300Responses
     * @dataProvider provide400Responses
     * @dataProvider provide500Responses
     * @param $code
     * @param $success
     */
    public function testIsNotSuccess($code, $success)
    {
        $response = $this
            ->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $response->method('getStatusCode')
            ->willReturn($code);

        $twitchResponse = Response::make($this->serializer)->withResponse($response, null);

        $this->assertFalse($twitchResponse->isSuccess());
    }

    /**
     * @dataProvider provideEmptySuccessfulResponse
     * @param $response
     * @param $headers
     */
    public function testPassthroughMethods($response, $headers)
    {
        // To cover getStatusCode() in Response
        $this->assertEquals(Http::HTTP_OK, $response->getStatusCode());

        $this->assertCount(1, $response->getHeaders());
    }

    /**
     * @dataProvider provideEmptySuccessfulResponse
     * @param $response
     * @param $headers
     */
    public function testGetType($response, $headers)
    {
        // To cover getType() in Response
        $this->assertNull($response->getType());
    }

    /**
     * @return Generator
     */
    public function provideEmptySuccessfulResponse()
    {
        /** @var \Faker\Generator|Twitch|MiscProvider $faker */
        $faker = Factory::create();
        $faker->addProvider(new Twitch($faker));

        $this->setUpSerializer();

        $header = u($faker->randomAlphanumericString(10, 'abcdefghijkmnopqrstuvwxyz'))->lower()->prepend('x-')->toString();

        $value = $faker->word();
        $headers[$header] = $value;

        $ri = $this
            ->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $ri->method('getStatusCode')
            ->willReturn(Http::HTTP_OK);
        $ri->method('getHeaders')
            ->willReturn([
                $header => [
                    $value
                ]
            ]);

        yield ['response' => Response::make($this->serializer)->withResponse($ri, null), 'headers' => $headers];
    }

    /**
     * @dataProvider provideEmptySuccessfulResponse
     * @param $response
     * @param $providedHeaders
     */
    public function testGetHeaders($response, $providedHeaders)
    {
        $count = count($providedHeaders);
        $providedHeaderKey = array_key_first($providedHeaders);
        $providedHeaderValue = array_shift($providedHeaders);

        $headers = $response->getHeaders();
        $this->assertCount($count, $headers);
        $this->assertArrayHasKey($providedHeaderKey, $headers);
        $header = array_shift($headers);
        $header = array_shift($header);
        $this->assertEquals($providedHeaderValue, $header);
    }
}