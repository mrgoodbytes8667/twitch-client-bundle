<?php


namespace Bytes\TwitchClientBundle\Routing;


/**
 * Class TwitchUserOAuth
 * @package Bytes\TwitchClientBundle\Routing
 */
class TwitchUserOAuth extends AbstractTwitchOAuth
{
    /**
     * @var string
     */
    protected static $endpoint = 'user';

    /**
     * Return the client name
     * @return string
     */
    public static function getDefaultIndexName(): string
    {
        return 'TWITCH-USER';
    }
}