<?php


namespace Bytes\TwitchClientBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * Trait TwitchUserTrait
 * @package Bytes\TwitchClientBundle\Entity
 */
trait TwitchUserTrait
{
    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true, unique: true)]
    private $twitch_id;

    /**
     * @return string|null
     */
    public function getTwitchId(): ?string
    {
        return $this->twitch_id;
    }

    /**
     * @param string|null $twitch_id
     * @return $this
     */
    public function setTwitchId(?string $twitch_id): self
    {
        $this->twitch_id = $twitch_id;

        return $this;
    }
}