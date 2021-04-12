<?php


namespace Bytes\TwitchClientBundle\HttpClient\Retry;


use Bytes\HttpClient\Common\Retry\APIRetryStrategy;
use Exception;
use InvalidArgumentException;
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
    const RATELIMITHEADER = 'ratelimit-reset';

    /**
     * @param AsyncContext $context
     * @param TransportExceptionInterface|null $exception
     * @return int Amount of time to delay in milliseconds
     * @throws Exception
     */
    protected function getRateLimitDelay(AsyncContext $context, ?TransportExceptionInterface $exception): int
    {
        if (($context->getInfo('retry_count') ?? 0) > 1) {
            try {
                $resetAt = self::getHeaderValue($context->getHeaders(), self::RATELIMITHEADER);
            } catch (InvalidArgumentException $e) {
                return $this->calculateDelay($context, $exception);
            }

            $retries = ($context->getInfo('retry_count') ?? 0) + 1;
            if ($retries > 1) {
                $reset = ($resetAt - time()) * ($retries / 10);
                return $reset * 1000;
            }
        }

        return $this->calculateDelay($context, $exception);
    }
}