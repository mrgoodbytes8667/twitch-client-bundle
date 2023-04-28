<?php


namespace Bytes\TwitchClientBundle\Tests;


use Bytes\ResponseBundle\HttpClient\Retry\APIRetryStrategy;
use Bytes\Tests\Common\TestFullValidatorTrait;
use Bytes\TwitchClientBundle\HttpClient\Api\AbstractTwitchClient;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchEventSubClient;
use Bytes\TwitchClientBundle\HttpClient\Retry\TwitchRetryStrategy;
use Bytes\TwitchClientBundle\HttpClient\Token\AbstractTwitchTokenClient;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchAppTokenClient;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchAppTokenResponse;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenClient;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenResponse;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\TwitchClientBundle\Tests\MockHttpClient\MockClient;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
use Doctrine\Common\Annotations\AnnotationReader;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Trait TwitchClientSetupTrait
 * @package Bytes\TwitchClientBundle\Tests
 *
 * @todo
 *
 * @property UrlGeneratorInterface $urlGenerator
 */
trait TwitchClientSetupTrait
{
    use TestFullValidatorTrait, FullSerializerTrait, TestUrlGeneratorTrait;

    /**
     * @param HttpClientInterface|null $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchClient
     * @throws Exception
     */
    protected function setupBaseClient(?HttpClientInterface $httpClient = null, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = $this->getMockBuilder(TwitchClient::class)
            ->setConstructorArgs([$httpClient ?? MockClient::empty(), $dispatcher ?? new EventDispatcher(), new TwitchRetryStrategy(), Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::USER_AGENT, $defaultOptionsByRegexp, $defaultRegexp])
            ->onlyMethods(['getToken'])
            ->getMock();
        $client->method('getToken')
        ->willReturn(Token::createFromAccessToken(Fixture::BOT_TOKEN));

        return $this->postClientSetup($client, $dispatcher);
    }

    /**
     * @param HttpClientInterface|null $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchEventSubClient
     * @throws Exception
     */
    protected function setupEventSubClient(?HttpClientInterface $httpClient = null, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = $this->getMockBuilder(TwitchEventSubClient::class)
            ->setConstructorArgs([$httpClient ?? MockClient::empty(), $dispatcher ?? new EventDispatcher(), new TwitchRetryStrategy(), $this->urlGenerator, Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::HUB_SECRET, Fixture::USER_AGENT, '', $defaultOptionsByRegexp, $defaultRegexp])
            ->onlyMethods(['getToken'])
            ->getMock();
        $client->method('getToken')
            ->willReturn(Token::createFromAccessToken(Fixture::BOT_TOKEN));

        return $this->postClientSetup($client, $dispatcher);
    }

    /**
     * @param TwitchClient|AbstractTwitchTokenClient|TwitchAppTokenClient|TwitchUserTokenClient|TwitchEventSubClient $client
     * @return TwitchClient|AbstractTwitchTokenClient|TwitchAppTokenClient|TwitchUserTokenClient|TwitchEventSubClient
     */
    private function postClientSetup($client, ?EventDispatcher $dispatcher = null, ?string $responseClass = '\Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse')
    {
        $dispatcher = $dispatcher ?? new EventDispatcher();
        $client->setSerializer($this->serializer);
        $client->setValidator($this->validator);
        $client->setReader(new AnnotationReader());
        if (method_exists($client, 'setDispatcher')) {
            $client->setDispatcher($dispatcher ?? new EventDispatcher());
        }

        $response = $responseClass::make($this->serializer);
        if (method_exists($response, 'setDispatcher')) {
            $response->setDispatcher($dispatcher ?? new EventDispatcher());
        }
        
        $client->setResponse($response);
        return $client;
    }

    /**
     * @param HttpClientInterface|null $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchClient
     */
    protected function setupRealClient(?HttpClientInterface $httpClient = null, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = new TwitchClient($httpClient ?? MockClient::empty(), $dispatcher ?? new EventDispatcher(), new TwitchRetryStrategy(), Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::USER_AGENT, $defaultOptionsByRegexp, $defaultRegexp);

        return $this->postClientSetup($client, $dispatcher);
    }

    /**
     * @param HttpClientInterface|null $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchEventSubClient
     */
    protected function setupRealEventSubClient(?HttpClientInterface $httpClient = null, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = new TwitchEventSubClient($httpClient ?? MockClient::empty(), $dispatcher ?? new EventDispatcher(), new TwitchRetryStrategy(), $this->urlGenerator, Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::HUB_SECRET, Fixture::USER_AGENT, '', $defaultOptionsByRegexp, $defaultRegexp);

        return $this->postClientSetup($client, $dispatcher);
    }

    /**
     * @param HttpClientInterface|null $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchAppTokenClient
     */
    protected function setupAppTokenClient(?HttpClientInterface $httpClient = null, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        return $this->setupTokenClient(TwitchAppTokenClient::class, TwitchAppTokenResponse::class, $httpClient, $dispatcher, $defaultOptionsByRegexp, $defaultRegexp);
    }

    /**
     * @param $class
     * @param $responseClass
     * @param HttpClientInterface|null $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchAppTokenClient|TwitchUserTokenClient
     */
    private function setupTokenClient($class, $responseClass, ?HttpClientInterface $httpClient = null, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = new $class($httpClient ?? MockClient::empty(), $dispatcher ?? new EventDispatcher(), Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::USER_AGENT, true, true, $defaultOptionsByRegexp, $defaultRegexp);
        return $this->postClientSetup($client, $dispatcher, $responseClass);
    }

    /**
     * @param HttpClientInterface|null $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchUserTokenClient
     */
    protected function setupUserTokenClient(?HttpClientInterface $httpClient = null, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        return $this->setupTokenClient(TwitchUserTokenClient::class, TwitchUserTokenResponse::class, $httpClient, $dispatcher, $defaultOptionsByRegexp, $defaultRegexp);
    }
}