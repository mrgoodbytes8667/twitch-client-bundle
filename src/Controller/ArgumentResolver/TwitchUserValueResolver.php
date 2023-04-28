<?php

namespace Bytes\TwitchClientBundle\Controller\ArgumentResolver;

use Bytes\TwitchClientBundle\Attribute\MapTwitchName;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchResponseBundle\Objects\Interfaces\TwitchUserInterface;
use Bytes\TwitchResponseBundle\Objects\Streams\Stream;
use Illuminate\Support\Arr;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use function is_int;
use function is_string;

/**
 * Converts and hydrates a {@see TwitchUserInterface} object. Assumes the value is a user ID if it is numeric.
 * Use {@see MapTwitchName} to force ID or Login usage.
 */
class TwitchUserValueResolver implements ValueResolverInterface
{
    /**
     * @param TwitchClient $client
     */
    public function __construct(private TwitchClient $client)
    {
    }

    /**
     * Returns the possible value(s).
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!is_subclass_of($argument->getType(), TwitchUserInterface::class)) {
            return [];
        }

        if ($argument->isVariadic()) {
            // only target route path parameters, which cannot be variadic.
            return [];
        }

        // do not support if no value can be resolved at all
        // letting the \Symfony\Component\HttpKernel\Controller\ArgumentResolver\DefaultValueResolver be used
        // or \Symfony\Component\HttpKernel\Controller\ArgumentResolver fail with a meaningful error.
        if (!$request->attributes->has($argument->getName())) {
            return [];
        }

        $value = $request->attributes->get($argument->getName());

        if (null === $value) {
            return [null];
        }

        // In theory, this shouldn't be possible...
        if ($value instanceof TwitchUserInterface) {
            return [$value];
        }

        if (!is_int($value) && !is_string($value)) {
            throw new LogicException(sprintf('Could not resolve the "%s $%s" controller argument: expecting an int or string, got "%s".', $argument->getType(), $argument->getName(), get_debug_type($value)));
        }
        $method = is_numeric($value) ? 'id' : 'login';

        if ($attributes = $argument->getAttributes(MapTwitchName::class, ArgumentMetadata::IS_INSTANCEOF)) {
            /** @var MapTwitchName $attribute */
            $attribute = $attributes[0];
            $method = $attribute->useName ? 'login' : 'id';
        }

        try {
            $isStream = is_a($argument->getType(), Stream::class, allow_string: true);
            if ($isStream) {
                $ids = [];
                $logins = [];
                if ($method === 'login') {
                    $logins[] = $value;
                } else {
                    $ids[] = $value;
                }
                $response = $this->client->getStreams(userIds: $ids, logins: $logins);
            } else {
                if ($method === 'login') {
                    $response = $this->client->getUser(login: $value);
                } else {
                    $response = $this->client->getUser(id: $value);
                }
            }
            if (!$response->isSuccess()) {
                throw new NotFoundHttpException(sprintf('Could not resolve the "%s $%s" controller argument: ', $argument->getType(), $argument->getName()));
            }
            $user = $response
                ->deserialize();
            if ($isStream) {
                $user = Arr::first($user->getData());
            }
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface|BadRequestHttpException $e) {
            throw new NotFoundHttpException(sprintf('Could not resolve the "%s $%s" controller argument: ', $argument->getType(), $argument->getName()) . $e->getMessage(), $e);
        }

        return [$user];
    }
}
