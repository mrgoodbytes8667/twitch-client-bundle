<?php


namespace Bytes\TwitchClientBundle\Entity;


/**
 * Interface TwitchUserInterface
 * @package Bytes\TwitchClientBundle\Entity
 */
interface TwitchUserInterface
{
    /**
     * @return string|null
     */
    public function getTwitchId(): ?string;

    /**
     * @param string|null $twitch_id
     * @return $this
     */
    public function setTwitchId(?string $twitch_id);
}