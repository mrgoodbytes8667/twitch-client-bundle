<?php

namespace Bytes\TwitchClientBundle\Controller\ArgumentResolver;

use Bytes\TwitchClientBundle\Attribute\MapTwitchGame;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchResponseBundle\Objects\Interfaces\GameInterface;
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
 * Converts and hydrates a {@see GameInterface} object. Assumes the value is a game ID if it is numeric.
 * Use {@see MapTwitchGame} to force name or igdb ID usage.
 */
class TwitchGameValueResolver implements ValueResolverInterface
{
    /**
     * @param TwitchClient $client
     */
    public function __construct(private readonly TwitchClient $client)
    {
    }

    /**
     * Returns the possible value(s).
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!is_subclass_of($argument->getType(), GameInterface::class)) {
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
        if ($value instanceof GameInterface) {
            return [$value];
        }

        if (!is_int($value) && !is_string($value)) {
            throw new LogicException(sprintf('Could not resolve the "%s $%s" controller argument: expecting an int or string, got "%s".', $argument->getType(), $argument->getName(), get_debug_type($value)));
        }
        
        $method = is_numeric($value) ? 'id' : 'name';

        if ($attributes = $argument->getAttributes(MapTwitchGame::class, ArgumentMetadata::IS_INSTANCEOF)) {
            /** @var MapTwitchGame $attribute */
            $attribute = $attributes[0];
            if ($attribute->useIgdbId) {
                $method = 'igdb_id';
            } elseif ($attribute->useName) {
                $method = 'name';
            }
        }

        try {
            switch ($method) {
                case 'id':
                    $response = $this->client->getGame(id: $value);
                    break;
                case 'name':
                    $response = $this->client->getGame(name: $value);
                    break;
                case 'igdb_id':
                    $response = $this->client->getGame(igdbId: $value);
                    break;
            }
            
            if (!$response->isSuccess()) {
                throw new NotFoundHttpException(sprintf('Could not resolve the "%s $%s" controller argument: ', $argument->getType(), $argument->getName()));
            }
            
            $user = $response
                ->deserialize();
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface|BadRequestHttpException $e) {
            throw new NotFoundHttpException(sprintf('Could not resolve the "%s $%s" controller argument: ', $argument->getType(), $argument->getName()) . $e->getMessage(), $e);
        }

        return [$user];
    }
}
