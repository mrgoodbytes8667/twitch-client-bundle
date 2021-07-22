<?php


namespace Bytes\TwitchClientBundle\HttpClient\Response;


use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchResponseBundle\Objects\Tags\TagsResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class TwitchAllStreamTagsResponse
 * @package Bytes\TwitchClientBundle\HttpClient\Response
 */
class TwitchAllStreamTagsResponse extends TwitchResponse
{
    /**
     * @param bool $throw
     * @param array $context
     * @param string|null $type
     * @return TagsResponse
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws NoTokenException
     */
    public function deserialize(bool $throw = true, array $context = [], ?string $type = null)
    {
        /** @var TagsResponse $origResults */
        $origResults = parent::deserialize($throw, $context, $type);
        if (!$this->getFollowPagination() || empty($this->getClient()) || $this->getLimit() <= 0) {
            $origResults->setPagination(null);
            return $origResults;
        }
        $results = $origResults;
        while (!empty($results->getPagination()?->getCursor()) && count($origResults->getData()) <= $this->getLimit()) {
            $results = $this->getClient()
                ->getAllStreamTags(ids: $this->getIds(), limit: $this->getLimit(), throw: false,
                    after: $results->getPagination()?->getCursor(), followPagination: false)
                ->deserialize(throw: $throw, context: $context, type: $type);
            $origResults->setData(array_merge($origResults->getData(), $results->getData()));
            $origResults->setPagination($results->getPagination());
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

    /**
     * @return string[]|null
     */
    protected function getIds()
    {
        if (empty($this->getExtraParams())) {
            return null;
        }
        if (!array_key_exists('ids', $this->getExtraParams())) {
            return null;
        }
        return $this->getExtraParams()['ids'];
    }
}