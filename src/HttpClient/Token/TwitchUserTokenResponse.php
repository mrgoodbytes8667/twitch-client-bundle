<?php


namespace Bytes\TwitchClientBundle\HttpClient\Token;


use Bytes\ResponseBundle\Enums\TokenSource;
use Bytes\ResponseBundle\HttpClient\Response\TokenResponse;

/**
 * Class TwitchUserTokenResponse
 * @package Bytes\TwitchClientBundle\HttpClient\Token
 */
class TwitchUserTokenResponse extends TokenResponse
{
    use TwitchTokenResponseTrait;

    /**
     * Returns the TokenSource for the token
     * @return TokenSource
     */
    protected static function getTokenSource(): TokenSource
    {
        return TokenSource::user();
    }
}