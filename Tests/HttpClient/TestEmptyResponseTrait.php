<?php


namespace Bytes\TwitchClientBundle\Tests\HttpClient;


use Bytes\TwitchClientBundle\HttpClient\TwitchResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Trait TestEmptyResponseTrait
 * @package Bytes\TwitchClientBundle\Tests\HttpClient
 *
 * @method TwitchResponse setupResponse(?string $fixtureFile = null, $content = null, int $code = Response::HTTP_OK, $type = \stdClass::class, ?string $exception = null)
 * @method assertTrue($condition, string $message = '')
 * @method assertResponseStatusCodeNotSame(ResponseInterface|TwitchResponse $response, int $expectedCode, string $message = '')
 */
trait TestEmptyResponseTrait
{
    /**
     *
     */
    public function testSuccess()
    {
        $this->assertTrue($this
            ->setupResponse(code: Response::HTTP_NO_CONTENT)
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
        $this->assertResponseStatusCodeNotSame($response, Response::HTTP_NO_CONTENT);
    }
}