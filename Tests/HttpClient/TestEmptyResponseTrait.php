<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient;


use Bytes\ResponseBundle\HttpClient\Response\Response;
use Symfony\Component\HttpFoundation\Response as Http;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Trait TestEmptyResponseTrait
 * @package Bytes\TwitchClientBundle\Tests\HttpClient
 *
 * @method Response setupResponse(?string $fixtureFile = null, $content = null, int $code = Http::HTTP_OK, $type = \stdClass::class, ?string $exception = null)
 * @method assertTrue($condition, string $message = '')
 * @method assertResponseStatusCodeNotSame(ResponseInterface|Response $response, int $expectedCode, string $message = '')
 */
trait TestEmptyResponseTrait
{
    /**
     *
     */
    public function testSuccess()
    {
        $this->assertTrue($this
            ->setupResponse(code: Http::HTTP_NO_CONTENT)
            ->isSuccess());
    }

    /**
     * This isn't a fatal error, but we'll test for it
     */
    public function testSuccessInvalidReturnCode()
    {
        $response = $this
            ->setupResponse();
        $this->assertTrue($response->isSuccess());
        $this->assertResponseStatusCodeNotSame($response, Http::HTTP_NO_CONTENT);
    }
}