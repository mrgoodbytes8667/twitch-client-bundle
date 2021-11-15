<?php


namespace Bytes\TwitchClientBundle\Security\Voters;


use Bytes\ResponseBundle\Handler\Locator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class TwitchHubSignatureVoter
 * Validates a Twitch EventSub/Webhook signature
 * @package Bytes\TwitchClientBundle\Security\Voters
 *
 * @example @Security("is_granted('TWITCH_HUBSIGNATURE_EVENTSUB', request)")
 * IsGranted() needs an attribute of TWITCH_HUBSIGNATURE_EVENTSUB to check an EventSub signature, or
 * TWITCH_HUBSIGNATURE_WEBHOOK for a webhook signature. The subject must be the request object.
 *
 * @link https://symfony.com/doc/current/security/voters.html
 * @link https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/security.html#security
 */
class TwitchHubSignatureVoter extends Voter
{
    const ATTRIBUTE_EVENTSUB = 'TWITCH_HUBSIGNATURE_EVENTSUB';
    const ATTRIBUTE_WEBHOOK = 'TWITCH_HUBSIGNATURE_WEBHOOK';

    /**
     * TwitchHubSignatureVoter constructor.
     * @param Locator $twitchSignatureLocator
     */
    public function __construct(private Locator $twitchSignatureLocator)
    {
    }

    /**
     * Determines if the attribute and subject are supported by this voter.
     *
     * @param string $attribute An attribute
     * @param mixed $subject The subject to secure, e.g. an object the user wants to access or any other PHP type
     *
     * @return bool True if the attribute and subject are supported, false otherwise
     */
    protected function supports(string $attribute, $subject): bool
    {
        if ($attribute !== self::ATTRIBUTE_EVENTSUB && $attribute !== self::ATTRIBUTE_WEBHOOK) {
            return false;
        }
        if($attribute === self::ATTRIBUTE_WEBHOOK)
        {
            trigger_deprecation('mrgoodbytes8667/twitch-client-bundle', '0.3.2', 'The "%s()" attribute has been deprecated. Webhooks are no longer supported by Twitch', self::ATTRIBUTE_WEBHOOK);
        }
        if (!($subject instanceof Request)) {
            return false;
        }
        if ($subject->headers->has('skip-signature') || $subject->query->has('skip-signature')) {
            return false;
        }
        if ($attribute === self::ATTRIBUTE_EVENTSUB && $this->twitchSignatureLocator->has('EVENTSUB')) {
            return true;
        } elseif ($attribute === self::ATTRIBUTE_WEBHOOK && $this->twitchSignatureLocator->has('WEBHOOK')) {
            return true;
        }
        return false;
    }

    /**
     * Perform a single access check operation on a given attribute, subject and token.
     * It is safe to assume that $attribute and $subject already passed the "supports()" method check.
     *
     * @param string $attribute
     * @param Request $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if ($attribute === self::ATTRIBUTE_EVENTSUB) {
            $validator = $this->twitchSignatureLocator->get('EVENTSUB');
        } else {
            trigger_deprecation('mrgoodbytes8667/twitch-client-bundle', '0.3.2', 'The "%s()" attribute has been deprecated. Webhooks are no longer supported by Twitch', self::ATTRIBUTE_WEBHOOK);
            $validator = $this->twitchSignatureLocator->get('WEBHOOK');
        }
        return $validator->validateHubSignature($subject->headers, $subject->getContent(), false);
    }
}