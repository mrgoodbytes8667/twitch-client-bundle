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

    public static $tokenSource = 'user';
}