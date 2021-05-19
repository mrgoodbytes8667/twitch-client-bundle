<?php

namespace Bytes\TwitchClientBundle\Tests\EventListener;

use Bytes\Common\Faker\Twitch\TestTwitchFakerTrait;
use Bytes\ResponseBundle\Enums\TokenSource;
use Bytes\ResponseBundle\Event\RevokeTokenEvent;
use Bytes\ResponseBundle\Handler\HttpClientLocator;
use Bytes\TwitchClientBundle\EventListener\RevokeTokenSubscriber;
use Bytes\TwitchClientBundle\HttpClient\Token\TwitchUserTokenClient;
use Bytes\TwitchResponseBundle\Objects\OAuth2\Token;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class RevokeTokenSubscriberTest
 * @package Bytes\TwitchClientBundle\Tests\EventListener
 */
class RevokeTokenSubscriberTest extends TestCase
{
    use TestTwitchFakerTrait;

    /**
     * @dataProvider provideTokenDetails
     * @param $identifier
     * @param $tokenSource
     * @throws TransportExceptionInterface
     */
    public function testOnRevokeToken($identifier, $tokenSource)
    {
        $token = Token::createFromAccessToken($this->faker->accessToken());
        $token->setIdentifier($identifier)
            ->setTokenSource($tokenSource);
        $event = RevokeTokenEvent::new($token);

        $userClient = $this->getMockBuilder(TwitchUserTokenClient::class)->disableOriginalConstructor()->getMock();

        $locator = $this->getMockBuilder(HttpClientLocator::class)->disableOriginalConstructor()->getMock();
        $locator->method('getTokenClient')
            ->willReturn($userClient);

        $subscriber = new RevokeTokenSubscriber($locator);

        $this->assertInstanceOf(RevokeTokenEvent::class, $subscriber->onRevokeToken($event));
    }

    /**
     * @return Generator
     */
    public function provideTokenDetails()
    {
        $this->setupFaker();
        foreach (array_merge(['TWITCH'], $this->faker->words(3)) as $identifier) {
            foreach ([TokenSource::user(), TokenSource::app(), TokenSource::id()] as $tokenSource) {
                yield ['identifier' => $identifier, 'tokenSource' => $tokenSource];
            }
        }
    }
}