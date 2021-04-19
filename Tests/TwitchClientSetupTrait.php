<?php


namespace Bytes\TwitchClientBundle\Tests;


use Bytes\Tests\Common\TestFullValidatorTrait;
use Bytes\TwitchClientBundle\HttpClient\Response\TwitchResponse;
use Bytes\TwitchClientBundle\HttpClient\Retry\TwitchRetryStrategy;
use Bytes\TwitchClientBundle\HttpClient\TwitchClient;
use Bytes\TwitchClientBundle\HttpClient\TwitchTokenClient;
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
        $client = new TwitchClient($httpClient, new TwitchRetryStrategy(), $dispatcher ?? new EventDispatcher(), $this->urlGenerator, Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::HUB_SECRET, Fixture::USER_AGENT, '', $defaultOptionsByRegexp, $defaultRegexp);
        return $this->postClientSetup($client, $dispatcher);
    }

    /**
     * @param TwitchClient|TwitchTokenClient $client
     * @return TwitchClient|TwitchTokenClient
     */
    private function postClientSetup($client, ?EventDispatcher $dispatcher = null)
    {
        $client->setSerializer($this->serializer);
        $client->setValidator($this->validator);

        $response = TwitchResponse::make($this->serializer);
        $response->setDispatcher($dispatcher ?? new EventDispatcher());
        $client->setResponse($response);
        return $client;
    }

    /**
     * @param HttpClientInterface $httpClient
     * @param EventDispatcher|null $dispatcher
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchTokenClient
     */
    protected function setupTokenClient(HttpClientInterface $httpClient, ?EventDispatcher $dispatcher = null, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = new TwitchTokenClient($httpClient, new TwitchRetryStrategy(), $dispatcher ?? new EventDispatcher(), $this->urlGenerator, Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::HUB_SECRET, Fixture::USER_AGENT, '', $defaultOptionsByRegexp, $defaultRegexp);
        return $this->postClientSetup($client, $dispatcher);
    }
}