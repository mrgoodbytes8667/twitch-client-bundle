<?php


namespace Bytes\TwitchClientBundle\Services;


/**
 * Class TwitchUserOAuth
 * @package Bytes\TwitchClientBundle\Services
 */
class TwitchUserOAuth extends AbstractTwitchOAuth
{
    /**
     * @var string
     */
    protected static $endpoint = 'user';
}