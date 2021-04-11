<?php


namespace Bytes\TwitchClientBundle\HttpClient\Retry;


use Bytes\HttpClient\Common\Retry\APIRetryStrategy;
use Exception;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class TwitchRetryStrategy
 * @package Bytes\TwitchClientBundle\HttpClient\Retry
 */
class TwitchRetryStrategy extends APIRetryStrategy implements RetryStrategyInterface
{
    /**
     *
     */
    const RATELIMITHEADER = 'x-ratelimit-reset-after';

    /**
     * @param AsyncContext $context
     * @param TransportExceptionInterface|null $exception
     * @return int Amount of time to delay in milliseconds
     * @throws Exception
     */
    protected function getRateLimitDelay(AsyncContext $context, ?TransportExceptionInterface $exception): int
    {
        $reset = $this->getReset($context) ?? -1;
        if ($reset > 0) {
            return $reset * 1000;
        } else {
            return $this->calculateDelay($context, $exception);
        }
    }

    /**
     * @param AsyncContext $context
     * @return int
     */
    protected function getReset(AsyncContext $context)
    {
        if (($context->getInfo('retry_count') ?? 0) > 1 && array_key_exists(self::RATELIMITHEADER, $context->getHeaders())) {
            $header = $context->getHeaders()[self::RATELIMITHEADER];
            if (array_key_exists(0, $header)) {
                return $context->getHeaders()[self::RATELIMITHEADER][0];
            }
        }

        return -1;
    }
}