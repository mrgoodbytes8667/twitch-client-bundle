<?php


namespace Bytes\TwitchClientBundle\HttpClient\Response;


use Bytes\TwitchResponseBundle\Objects\Users\User;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TwitchUserResponse extends TwitchResponse
{
    /**
     * @param bool $throw
     * @param array $context
     * @param string|null $type
     * @return User|null
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function deserialize(bool $throw = true, array $context = [], ?string $type = null)
    {
        /** @var User[] $results */
        $results = parent::deserialize($throw, $context, $type);
        if (empty($results)) {
            return $results;
        }
        return array_shift($results);
    }
}