<?php


namespace Bytes\TwitchClientBundle\Routing;


/**
 * Class TwitchAppOAuth
 * @package Bytes\TwitchClientBundle\Routing
 */
class TwitchAppOAuth extends AbstractTwitchOAuth
{
    /**
     * @var string
     */
    protected static $endpoint = 'app';

    /**
     * Return the client name
     * @return string
     */
    public static function getDefaultIndexName(): string
    {
        return 'TWITCH-APP';
    }
}