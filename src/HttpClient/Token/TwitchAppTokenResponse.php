<?php


namespace Bytes\TwitchClientBundle\HttpClient\Token;


use Bytes\ResponseBundle\Enums\TokenSource;
use Bytes\ResponseBundle\HttpClient\Response\TokenResponse;

/**
 * Class TwitchAppTokenResponse
 * @package Bytes\TwitchClientBundle\HttpClient\Token
 */
class TwitchAppTokenResponse extends TokenResponse
{
    use TwitchTokenResponseTrait;

    protected static $tokenSource = 'app';
}