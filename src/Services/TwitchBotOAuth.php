<?php


namespace Bytes\TwitchClientBundle\Services;


/**
 * Class TwitchBotOAuth
 * @package Bytes\TwitchClientBundle\Services
 */
class TwitchBotOAuth extends AbstractTwitchOAuth
{
    /**
     * @var string
     */
    protected static $endpoint = 'bot';
}