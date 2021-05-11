<?php


namespace Bytes\TwitchClientBundle\Routing;


/**
 * Class TwitchLoginOAuth
 * @package Bytes\TwitchClientBundle\Routing
 */
class TwitchLoginOAuth extends AbstractTwitchOAuth
{
    /**
     * @var string
     */
    protected static $endpoint = 'login';

    /**
     * Return the client name
     * @return string
     */
    public static function getDefaultIndexName(): string
    {
        return 'TWITCH-LOGIN';
    }
}