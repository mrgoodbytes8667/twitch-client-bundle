<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient;


use Bytes\ResponseBundle\HttpClient\Response\Response;
use Bytes\Tests\Common\ClientExceptionResponseProviderTrait;
use Symfony\Component\HttpFoundation\Response as Http;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Trait TestTwitchResponseTrait
 * @package Bytes\TwitchClientBundle\Tests\HttpClient
 *
 * @method assertInstanceOf(string $expected, $actual, string $message = '')
 * @method assertEquals($expected, $actual, string $message = '')
 * @method assertCount(int $expectedCount, $haystack, string $message = '')
 * @method assertNull($actual, string $message = '')
 * @method expectException(string $exception)
 * @method expectExceptionMessage(string $message)
 * @method setupClient(HttpClientInterface $httpClient)
 * @method Response setupResponse(?string $fixtureFile = null, $content = null, int $code = Http::HTTP_OK, $type = \stdClass::class, ?string $exception = null)
 * @property SerializerInterface $serializer
 */
trait TestTwitchResponseTrait
{
    use ClientExceptionResponseProviderTrait, ValidateUserTrait, TestEmptyResponseTrait {
        TestEmptyResponseTrait::testSuccess as testDeleteWebhookMessage;
        TestEmptyResponseTrait::testSuccessInvalidReturnCode as testDeleteWebhookMessageInvalidReturnCode;
    }

    /**
     * @dataProvider provideClientExceptionResponses
     *
     * @param int $code
     *
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testResponseFailure(int $code)
    {
        $this->expectException(ClientExceptionInterface::class);
        $this->expectExceptionMessage(sprintf('HTTP %d returned for', $code));

        $test = $this->setupResponse(code: $code);

        $test->deserialize();
    }
}