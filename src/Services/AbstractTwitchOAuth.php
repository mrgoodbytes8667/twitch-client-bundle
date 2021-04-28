<?php


namespace Bytes\TwitchClientBundle\Services;


use BadMethodCallException;
use Bytes\ResponseBundle\Services\AbstractOAuth;
use Bytes\ResponseBundle\Services\OAuthPromptInterface;
use Bytes\TwitchClientBundle\HttpClient\TwitchClientEndpoints;
use Bytes\TwitchResponseBundle\Enums\OAuthForceVerify;
use Bytes\TwitchResponseBundle\Enums\OAuthScopes;
use Symfony\Component\String\UnicodeString;
use function Symfony\Component\String\u;

/**
 * Class AbstractTwitchOAuth
 * @package Bytes\TwitchClientBundle\Services
 */
abstract class AbstractTwitchOAuth extends AbstractOAuth
{
    /**
     * @var string
     */
    protected static $promptKey = 'force_verify';

    /**
     * @var string
     */
    protected static $baseAuthorizationCodeGrantURL = '';

    /**
     * @param $value
     * @param $key
     */
    protected static function walkHydrateScopes(&$value, $key)
    {
        $value = (new OAuthScopes($value))->value;
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultScopes(): array
    {
        return [];
    }

    /**
     * Returns the $prompt argument for getAuthorizationCodeGrantURL() after normalization and validation
     * @param OAuthPromptInterface|string|bool|null $prompt
     * @return string|bool
     *
     * @throws BadMethodCallException
     */
    protected function normalizePrompt(OAuthPromptInterface|bool|string|null $prompt)
    {
        if ($prompt instanceof OAuthForceVerify) {
            return $prompt->prompt();
        } elseif (is_bool($prompt)) {
            return $prompt;
        } else {
            return false;
        }
    }

    /**
     * @return UnicodeString
     */
    protected static function getBaseAuthorizationCodeGrantURL(): UnicodeString
    {
        return u(TwitchClientEndpoints::ENDPOINT_TWITCH_OAUTH)->ensureEnd('/')->append('oauth2/authorize')->ensureEnd('?');
    }
}