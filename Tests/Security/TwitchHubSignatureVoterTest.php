<?php

namespace Bytes\TwitchClientBundle\Tests\Security;

use Bytes\ResponseBundle\Handler\Locator;
use Bytes\TwitchClientBundle\Security\Voters\TwitchHubSignatureVoter;
use Bytes\TwitchResponseBundle\Request\EventSubSignature;
use Bytes\TwitchResponseBundle\Request\WebhookSignature;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Class TwitchHubSignatureVoterTest
 * @package Bytes\TwitchClientBundle\Tests\Security
 */
class TwitchHubSignatureVoterTest extends TestCase
{
    /**
     *
     */
    public function testVoteEventSub()
    {
        $voter = $this->getVoter(EventSubSignature::class);

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $this->getMockBuilder(TokenInterface::class)->getMock(),
                new Request(),
                [TwitchHubSignatureVoter::ATTRIBUTE_EVENTSUB]));
    }

    /**
     * @param string $signatureClass
     * @param bool $has
     * @return TwitchHubSignatureVoter
     */
    protected function getVoter(string $signatureClass, bool $has = true)
    {
        $locator = $this->getMockBuilder(Locator::class)->disableOriginalConstructor()->getMock();
        $locator->method('get')
            ->willReturn(new $signatureClass('abc123'));
        $locator->method('has')
            ->willReturn($has);
        return new TwitchHubSignatureVoter($locator);
    }

    /**
     *
     */
    public function testVoteWebhook()
    {
        $voter = $this->getVoter(WebhookSignature::class);

        $this->assertEquals(VoterInterface::ACCESS_DENIED,
            $voter->vote(
                $this->getMockBuilder(TokenInterface::class)->getMock(),
                new Request(),
                [TwitchHubSignatureVoter::ATTRIBUTE_WEBHOOK]));
    }

    /**
     *
     */
    public function testVoteSkipSignature()
    {
        $voter = $this->getVoter(EventSubSignature::class);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $this->getMockBuilder(TokenInterface::class)->getMock(),
                new Request(server: ['HTTP_skip-signature' => 1]),
                [TwitchHubSignatureVoter::ATTRIBUTE_EVENTSUB]));
    }

    /**
     *
     */
    public function testVoteInvalidAttribute()
    {
        $voter = $this->getVoter(EventSubSignature::class);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $this->getMockBuilder(TokenInterface::class)->getMock(),
                new Request(),
                ['abc123']));
    }

    /**
     *
     */
    public function testVoteInvalidSubject()
    {
        $voter = $this->getVoter(EventSubSignature::class);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $this->getMockBuilder(TokenInterface::class)->getMock(),
                new stdClass(),
                [TwitchHubSignatureVoter::ATTRIBUTE_EVENTSUB]));
    }

    /**
     *
     */
    public function testVoteMissingLocator()
    {
        $voter = $this->getVoter(EventSubSignature::class, has: false);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN,
            $voter->vote(
                $this->getMockBuilder(TokenInterface::class)->getMock(),
                new Request(),
                [TwitchHubSignatureVoter::ATTRIBUTE_EVENTSUB]));
    }
}