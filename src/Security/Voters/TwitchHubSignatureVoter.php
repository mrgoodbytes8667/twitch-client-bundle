<?php


namespace Bytes\TwitchClientBundle\Security\Voters;


use Bytes\TwitchResponseBundle\Request\EventSubSignature;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class TwitchHubSignatureVoter
 * Validates a Twitch EventSub/Webhook signature
 * @package Bytes\TwitchClientBundle\Security\Voters
 *
 * @example @Security("is_granted('TWITCH_HUBSIGNATURE_EVENTSUB', request)")
 * IsGranted() needs an attribute of TWITCH_HUBSIGNATURE_EVENTSUB to check an EventSub signature. The subject must be
 * the request object.
 *
 * @link https://symfony.com/doc/current/security/voters.html
 * @link https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/security.html#security
 */
class TwitchHubSignatureVoter extends Voter
{
    /**
     * @var string
     */
    const ATTRIBUTE_EVENTSUB = 'TWITCH_HUBSIGNATURE_EVENTSUB';

    /**
     * @param EventSubSignature $twitchSignatureLocator
     */
    public function __construct(private readonly EventSubSignature $twitchSignatureLocator)
    {
    }

    /**
     * Return false if your voter doesn't support the given attribute. Symfony will cache
     * that decision and won't call your voter again for that attribute.
     */
    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === self::ATTRIBUTE_EVENTSUB;
    }

    /**
     * Return false if your voter doesn't support the given subject type. Symfony will cache
     * that decision and won't call your voter again for that subject type.
     *
     * @param string $subjectType The type of the subject inferred by `get_class()` or `get_debug_type()`
     */
    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, Request::class, true);
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
        if ($attribute !== self::ATTRIBUTE_EVENTSUB) {
            return false;
        }
        
        if (!($subject instanceof Request)) {
            return false;
        }
        
        if ($subject->headers->has('skip-signature') || $subject->query->has('skip-signature')) {
            return false;
        }
        
        return true;
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
            return $this->twitchSignatureLocator->validateHubSignature($subject->headers, $subject->getContent(), false);
        }

        return false;
    }
}