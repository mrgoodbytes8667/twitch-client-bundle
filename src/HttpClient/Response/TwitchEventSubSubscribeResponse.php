<?php


namespace Bytes\TwitchClientBundle\HttpClient\Response;


/**
 * Class TwitchEventSubSubscribeResponse
 * @package Bytes\TwitchClientBundle\HttpClient\Response
 */
class TwitchEventSubSubscribeResponse extends TwitchResponse
{
    /**
     * @return string|null
     */
    public function getUrl()
    {
        if (empty($this->getExtraParams())) {
            return null;
        }
        
        if (!array_key_exists('url', $this->getExtraParams())) {
            return null;
        }
        
        return $this->getExtraParams()['url'];
    }
}