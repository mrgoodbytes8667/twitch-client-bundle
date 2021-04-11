<?php


namespace Bytes\TwitchClientBundle\Tests;


use Bytes\TwitchClientBundle\HttpClient\TwitchBotClient;
use Bytes\TwitchClientBundle\HttpClient\TwitchClient;
use Bytes\TwitchClientBundle\HttpClient\TwitchResponse;
use Bytes\TwitchClientBundle\HttpClient\TwitchTokenClient;
use Bytes\TwitchClientBundle\HttpClient\TwitchUserClient;
use Bytes\TwitchClientBundle\HttpClient\Retry\TwitchRetryStrategy;
use Bytes\TwitchClientBundle\Tests\Fixtures\Fixture;
use Bytes\Tests\Common\TestFullSerializerTrait;
use Bytes\Tests\Common\TestFullValidatorTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Trait TwitchClientSetupTrait
 * @package Bytes\TwitchClientBundle\Tests
 *
 * @property UrlGeneratorInterface $urlGenerator
 */
trait TwitchClientSetupTrait
{
    use TestFullValidatorTrait, TestFullSerializerTrait;

    /**
     * @param HttpClientInterface $httpClient
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchClient
     */
    protected function setupBaseClient(HttpClientInterface $httpClient, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = new TwitchClient($httpClient, new TwitchRetryStrategy(), Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::BOT_TOKEN, Fixture::USER_AGENT, $defaultOptionsByRegexp, $defaultRegexp);
        return $this->postClientSetup($client);
    }

    /**
     * @param HttpClientInterface $httpClient
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchBotClient
     */
    protected function setupBotClient(HttpClientInterface $httpClient, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = new TwitchBotClient($httpClient, new TwitchRetryStrategy(), Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::BOT_TOKEN, Fixture::USER_AGENT, $defaultOptionsByRegexp, $defaultRegexp);
        return $this->postClientSetup($client);
    }

    /**
     * @param HttpClientInterface $httpClient
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchUserClient
     */
    protected function setupUserClient(HttpClientInterface $httpClient, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = new TwitchUserClient($httpClient, new TwitchRetryStrategy(), Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::USER_AGENT, $defaultOptionsByRegexp, $defaultRegexp);
        return $this->postClientSetup($client);
    }

    /**
     * @param HttpClientInterface $httpClient
     * @param array $defaultOptionsByRegexp
     * @param string|null $defaultRegexp
     * @return TwitchTokenClient
     */
    protected function setupTokenClient(HttpClientInterface $httpClient, array $defaultOptionsByRegexp = [], string $defaultRegexp = null)
    {
        $client = new TwitchTokenClient($httpClient, new TwitchRetryStrategy(), $this->urlGenerator, Fixture::CLIENT_ID, Fixture::CLIENT_SECRET, Fixture::USER_AGENT, $defaultOptionsByRegexp, $defaultRegexp);
        return $this->postClientSetup($client);
    }

    /**
     * @param TwitchClient|TwitchBotClient|TwitchUserClient|TwitchTokenClient $client
     * @return TwitchClient|TwitchBotClient|TwitchUserClient|TwitchTokenClient
     */
    private function postClientSetup($client)
    {
        $client->setSerializer($this->serializer);
        $client->setValidator($this->validator);
        $client->setResponse(TwitchResponse::make($this->serializer));
        return $client;
    }
}
