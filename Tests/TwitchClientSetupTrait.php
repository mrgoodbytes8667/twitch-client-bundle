<?php


namespace Bytes\TwitchClientBundle\Tests;


use Bytes\Tests\Common\TestFullValidatorTrait;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchClientBundle\HttpClient\Retry\TwitchRetryStrategy;
use Bytes\TwitchClientBundle\HttpClient\Token\AbstractTwitchTokenClient;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchAppTokenClient;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenClient;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
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
     * @param HttpClientInterface $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchClient
     */
    protected function setupBaseClient(HttpClientInterface $httpClient, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = new TwitchClient($httpClient, new TwitchRetryStrategy(), $this->urlGenerator, Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::HUB_SECRET, Fixture::USER_AGENT, '', $defaultOptionsByRegexp, $defaultRegexp);
        return $this->postClientSetup($client, $dispatcher);
    }

    /**
     * @param \Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient|AbstractTwitchTokenClient|TwitchAppTokenClient|\Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenClient $client
     * @return \Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient|AbstractTwitchTokenClient|TwitchAppTokenClient|TwitchUserTokenClient
     */
    private function postClientSetup($client, ?EventDispatcher $dispatcher = null)
    {
        $dispatcher = $dispatcher ?? new EventDispatcher();
        $client->setSerializer($this->serializer);
        $client->setValidator($this->validator);
        if(method_exists($client, 'setDispatcher'))
        {
            $client->setDispatcher($dispatcher ?? new EventDispatcher());
        }

        $response = TwitchResponse::make($this->serializer);
        if(method_exists($response, 'setDispatcher'))
        {
            $response->setDispatcher($dispatcher ?? new EventDispatcher());
        }
        $client->setResponse($response);
        return $client;
    }

    /**
     * @param HttpClientInterface $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchAppTokenClient
     */
    protected function setupAppTokenClient(HttpClientInterface $httpClient, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        return $this->setupTokenClient(TwitchAppTokenClient::class, $httpClient, $dispatcher, $defaultOptionsByRegexp, $defaultRegexp);
    }

    /**
     * @param HttpClientInterface $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return \Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenClient
     */
    protected function setupUserTokenClient(HttpClientInterface $httpClient, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        return $this->setupTokenClient(TwitchUserTokenClient::class, $httpClient, $dispatcher, $defaultOptionsByRegexp, $defaultRegexp);
    }

    /**
     * @param $class
     * @param HttpClientInterface $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchAppTokenClient|\Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenClient
     */
    private function setupTokenClient($class, HttpClientInterface $httpClient, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = new $class($httpClient, Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::USER_AGENT, $defaultOptionsByRegexp, $defaultRegexp);
        return $this->postClientSetup($client, $dispatcher);
    }
}