<?php

namespace Bytes\TwitchClientBundle\Tests\Event;

use Bytes\Common\Faker\TestFakerTrait;
use Bytes\ResponseBundle\Objects\Push;
use Bytes\TwitchClientBundle\Event\EventSubSubscriptionGenerateCallbackEvent;
use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\Interfaces\UserInterface;
use Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class EventSubSubscriptionGenerateCallbackEventTest
 * @package Bytes\TwitchClientBundle\Tests\Event
 */
class EventSubSubscriptionGenerateCallbackEventTest extends TestCase
{
    use TestFakerTrait;

    /**
     *
     */
    public function testNew()
    {
        $callbackName = $this->faker->unique()->word();
        $type = EventSubSubscriptionTypes::channelUpdate();
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user->method('getUserId')
            ->willReturn('123');
        $user->method('getLogin')
            ->willReturn('abc');
        $typeKey = $this->faker->unique()->word();
        $userKey = $this->faker->unique()->word();
        $addLogin = true;
        $loginKey = $this->faker->unique()->word();
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $extraParameters = ['q' => 'r'];
        $event = EventSubSubscriptionGenerateCallbackEvent::new($callbackName, $type, $user, $typeKey, $userKey, $addLogin, $loginKey, $extraParameters, $referenceType);

        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event);
        $this->assertCount(4, $event->getParameters());
        $this->assertFalse($event->hasUrl());
        $this->assertEquals($callbackName, $event->getCallbackName());
        $this->assertEquals($type, $event->getType());
        $this->assertEquals($user, $event->getUser());
        $this->assertEquals($typeKey, $event->getTypeKey());
        $this->assertEquals($userKey, $event->getUserKey());
        $this->assertTrue($event->getAddLogin());
        $this->assertEquals($loginKey, $event->getLoginKey());
        $this->assertEquals($referenceType, $event->getReferenceType());
    }

    /**
     *
     */
    public function testGetSetCallbackName()
    {
        $event = new EventSubSubscriptionGenerateCallbackEvent();
        $this->assertNull($event->getCallbackName());
        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->setCallbackName('abc123'));
        $this->assertEquals('abc123', $event->getCallbackName());
    }

    /**
     *
     */
    public function testGetSetTypeKey()
    {
        $event = new EventSubSubscriptionGenerateCallbackEvent();
        $this->assertNotEmpty($event->getTypeKey());
        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->setTypeKey('abc123'));
        $this->assertEquals('abc123', $event->getTypeKey());
    }

    /**
     *
     */
    public function testGetSetUserKey()
    {
        $event = new EventSubSubscriptionGenerateCallbackEvent();
        $this->assertNotEmpty($event->getUserKey());
        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->setUserKey('abc123'));
        $this->assertEquals('abc123', $event->getUserKey());
    }

    /**
     *
     */
    public function testGetSetLoginKey()
    {
        $event = new EventSubSubscriptionGenerateCallbackEvent();
        $this->assertNotEmpty($event->getLoginKey());
        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->setLoginKey('abc123'));
        $this->assertEquals('abc123', $event->getLoginKey());
    }

    /**
     *
     */
    public function testGetSetAddLogin()
    {
        $event = new EventSubSubscriptionGenerateCallbackEvent();
        $this->assertTrue($event->getAddLogin());
        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->setAddLogin(false));
        $this->assertFalse($event->getAddLogin());
    }

    /**
     *
     */
    public function testGetSetReferenceType()
    {
        $event = new EventSubSubscriptionGenerateCallbackEvent();
        $this->assertEquals(UrlGeneratorInterface::ABSOLUTE_URL, $event->getReferenceType());
        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->setReferenceType(UrlGeneratorInterface::NETWORK_PATH));
        $this->assertEquals(UrlGeneratorInterface::NETWORK_PATH, $event->getReferenceType());

        $event = new EventSubSubscriptionGenerateCallbackEvent(referenceType: null);
        $this->assertEquals(UrlGeneratorInterface::ABSOLUTE_URL, $event->getReferenceType());
        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->setReferenceType(UrlGeneratorInterface::NETWORK_PATH));
        $this->assertEquals(UrlGeneratorInterface::NETWORK_PATH, $event->getReferenceType());
    }

    /**
     *
     */
    public function testGetAddRemoveParameters()
    {
        $event = new EventSubSubscriptionGenerateCallbackEvent();
        $this->assertEmpty($event->getParameters());
        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->addParameter('a', '1'));
        $this->assertCount(1, $event->getParameters());

        // Count should stay the same
        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->addParameter('a', '1'));
        $this->assertCount(1, $event->getParameters());

        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->addParameter('b', '2'));
        $this->assertCount(2, $event->getParameters());

        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->removeParameter('a'));
        $this->assertCount(1, $event->getParameters());
    }

    /**
     * @dataProvider provideParameters
     * @param $params
     * @param $count
     */
    public function testSetParameters($params, $count)
    {
        $event = new EventSubSubscriptionGenerateCallbackEvent();
        $this->assertEmpty($event->getParameters());

        $this->assertInstanceOf(EventSubSubscriptionGenerateCallbackEvent::class, $event->setParameters($params));
        $this->assertCount($count, $event->getParameters());
    }

    /**
     * @return Generator
     */
    public function provideParameters()
    {
        $arr = ['a' => '1', 'b' => 2];
        $params = Push::create($arr);
        yield ['params' => $arr, 'count' => 2];
        yield ['params' => $params, 'count' => 2];
        yield ['params' => null, 'count' => 0];
    }

    /**
     *
     */
    public function testIsGenerationSkipped()
    {
        $event = new EventSubSubscriptionGenerateCallbackEvent();
        $this->assertFalse($event->isGenerationSkipped());

        $event = new EventSubSubscriptionGenerateCallbackEvent(url: $this->faker->url());
        $this->assertTrue($event->isGenerationSkipped());
    }
}