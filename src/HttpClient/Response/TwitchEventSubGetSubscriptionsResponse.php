<?php


namespace Bytes\TwitchClientBundle\HttpClient\Response;


use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchEventSubClient;
use Bytes\TwitchResponseBundle\Enums\EventSubStatus;
use Bytes\TwitchResponseBundle\Enums\EventSubSubscriptionTypes;
use Bytes\TwitchResponseBundle\Objects\Games\GamesResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class TwitchEventSubGetSubscriptionsResponse
 * @package Bytes\TwitchClientBundle\HttpClient\Response
 */
class TwitchEventSubGetSubscriptionsResponse extends TwitchResponse
{
    /**
     * @param bool $throw
     * @param array $context
     * @param string|null $type
     * @return \Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Subscriptions
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws NoTokenException
     */
    public function deserialize(bool $throw = true, array $context = [], ?string $type = null)
    {
        /** @var \Bytes\TwitchResponseBundle\Objects\EventSub\Subscription\Subscriptions $origResults */
        $origResults = parent::deserialize($throw, $context, $type);
        if (!$this->getFollowPagination() || empty($this->getClient())) {
            $origResults->setPagination(null);
            return $origResults;
        }
        $results = $origResults;
        while (!empty($results->getPagination()?->getCursor())) {
            $results = $this->getClient()->eventSubGetSubscriptions($this->getFilter(), throw: false, after: $results->getPagination()?->getCursor(), followPagination: false)->deserialize();
            $origResults->setData(array_merge($origResults->getData(), $results->getData()));
            $origResults->setPagination($results->getPagination());
            $origResults->setTotal($origResults->getTotal() + $results->getTotal());
        }
        return $origResults;
    }

    /**
     * @return EventSubStatus|EventSubSubscriptionTypes|null
     */
    protected function getFilter()
    {
        if (empty($this->getExtraParams())) {
            return null;
        }
        if (!array_key_exists('filter', $this->getExtraParams())) {
            return null;
        }
        return $this->getExtraParams()['filter'];
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
     * @return TwitchEventSubClient|null
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
}