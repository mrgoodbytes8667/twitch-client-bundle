<?php


namespace Bytes\TwitchClientBundle\HttpClient\Response;


use Bytes\ResponseBundle\HttpClient\Response\Response;
use Bytes\ResponseBundle\Interfaces\ClientResponseInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class TwitchResponse
 * @package Bytes\TwitchClientBundle\HttpClient\Response
 */
class TwitchResponse extends Response
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param ClientResponseInterface $clientResponse
     * @param array $params Extra params handed to setExtraParams()
     * @return static
     */
    public static function makeFrom($clientResponse, array $params = []): static
    {
        $static = parent::makeFrom($clientResponse, $params);
        $static->setExtraParams($params);
        return $static;
    }

    /**
     * For any other parameters that are needed. Saved off by default, but this is meant to be overloaded if needed.
     * @param array $params
     * @return $this
     */
    public function setExtraParams(array $params = []): static
    {
        if(array_key_exists('dispatcher', $params) && empty($this->dispatcher))
        {
            $this->setDispatcher($params['dispatcher']);
            unset($params['dispatcher']);
        }
        return parent::setExtraParams($params);
    }

    /**
     * @return EventDispatcherInterface|null
     */
    public function getDispatcher(): ?EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     * @return $this
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher): self
    {
        $this->dispatcher = $dispatcher;
        return $this;
    }


}