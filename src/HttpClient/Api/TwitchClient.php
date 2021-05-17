<?php


namespace Bytes\TwitchClientBundle\HttpClient\Api;


use Bytes\ResponseBundle\Annotations\Client;

/**
 * Class TwitchClient
 * @package Bytes\TwitchClientBundle\HttpClient\Api
 *
 * @Client(identifier="TWITCH", tokenSource="app")
 */
class TwitchClient extends AbstractTwitchClient
{
    /**
     * Return the client name
     * @return string
     */
    public static function getDefaultIndexName(): string
    {
        return 'TWITCH';
    }
}