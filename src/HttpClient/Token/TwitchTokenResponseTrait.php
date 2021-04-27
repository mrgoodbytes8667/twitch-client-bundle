<?php


namespace Bytes\TwitchClientBundle\HttpClient\Token;


/**
 * Trait TwitchTokenResponseTrait
 * @package Bytes\TwitchClientBundle\HttpClient\Token
 */
trait TwitchTokenResponseTrait
{
    /**
     * Identifier used for differentiating different token providers
     * @var string
     */
    protected static $identifier = 'TWITCH';
}