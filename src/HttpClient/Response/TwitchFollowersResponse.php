<?php


namespace Bytes\TwitchClientBundle\HttpClient\Response;


use Bytes\TwitchClientBundle\HttpClient\TwitchClient;
use Bytes\TwitchResponseBundle\Objects\Follows\FollowersResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class TwitchFollowersResponse
 * @package Bytes\TwitchClientBundle\HttpClient\Response
 */
class TwitchFollowersResponse extends TwitchResponse
{
    /**
     * @param bool $throw
     * @param array $context
     * @param string|null $type
     * @return FollowersResponse|null
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function deserialize(bool $throw = true, array $context = [], ?string $type = null)
    {
        /** @var FollowersResponse $origResults */
        $origResults = parent::deserialize($throw, $context, $type);
        if (!$this->getFollowPagination() || empty($this->getClient())) {
            return $origResults;
        }
        $results = $origResults;
        while (!empty($results->getPagination()?->getCursor())) {
            $results = $this->getClient()->getFollows($this->getFromId(), $this->getToId(), $results->getPagination()?->getCursor(), $this->getLimit(), false)->deserialize();
            $origResults->setData(array_merge($origResults->getData(), $results->getData()));
            $origResults->setPagination($results->getPagination());
            $origResults->setTotal($origResults->getTotal() + $results->getTotal());
        }
        return $origResults;
    }

    /**
     * @return bool
     */
    protected function getFollowPagination()
    {
        if (empty($this->getExtraParams())) {
            return false;
        }
        if (!array_key_exists('followPagination', $this->getExtraParams())) {
            return false;
        }
        return $this->getExtraParams()['followPagination'];
    }

    /**
     * @return TwitchClient|null
     */
    protected function getClient()
    {
        if (empty($this->getExtraParams())) {
            return null;
        }
        if (!array_key_exists('client', $this->getExtraParams())) {
            return null;
        }
        return $this->getExtraParams()['client'];
    }

    /**
     * @return string|null
     */
    protected function getFromId()
    {
        if (empty($this->getExtraParams())) {
            return null;
        }
        if (!array_key_exists('fromId', $this->getExtraParams())) {
            return null;
        }
        return $this->getExtraParams()['fromId'];
    }

    /**
     * @return string|null
     */
    protected function getToId()
    {
        if (empty($this->getExtraParams())) {
            return null;
        }
        if (!array_key_exists('toId', $this->getExtraParams())) {
            return null;
        }
        return $this->getExtraParams()['toId'];
    }

    /**
     * @return int|null
     */
    protected function getLimit()
    {
        if (empty($this->getExtraParams())) {
            return null;
        }
        if (!array_key_exists('limit', $this->getExtraParams())) {
            return null;
        }
        return $this->getExtraParams()['limit'];
    }
}
