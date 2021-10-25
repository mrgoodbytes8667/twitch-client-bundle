<?php


namespace Bytes\TwitchClientBundle\Request;


use Bytes\ResponseBundle\Token\Exceptions\NoTokenException;
use Bytes\TwitchClientBundle\HttpClient\Api\TwitchClient;
use Bytes\TwitchResponseBundle\Objects\Users\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class TwitchUserConverter
 * Converts and hydrates a UserInterface
 *
 * @link https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html
 *
 * Due to the time necessary to communicate with the Twitch API, this converter is disabled by default
 * To use this converter, add the @ParamConverter() tag.
 *
 * <code>
 * // Using a route with a user param...
 * @Route("/some/route/to/{user}")
 * // Use the TwitchUserConverter
 * @ParamConverter("user", converter="bytes_twitch_client_user")
 * </code>
 */
class TwitchUserConverter implements ParamConverterInterface
{
    /**
     * @param TwitchClient $client
     */
    public function __construct(public TwitchClient $client)
    {
    }

    /**
     * Stores the object in the request.
     *
     * @param Request $request
     * @param ParamConverter $configuration Contains the name, class and options of the object
     *
     * @return bool True if the object has been successfully set, else false
     * @throws NoTokenException
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $param = $configuration->getName();

        if (!$request->attributes->has($param)) {
            return false;
        }

        $value = $request->attributes->get($param);

        if (!$value && $configuration->isOptional()) {
            $request->attributes->set($param, null);

            return true;
        }

        $options = $configuration->getOptions();

        $class = $configuration->getClass();

        $instance = new $class();
        if (!($instance instanceof User)) {
            return false;
        }
        $instance->setUserId($value);

        try {
            $response = $this->client->getUser(id: $value);
            if (!$response->isSuccess()) {
                return false;
            }
            $user = $response
                ->deserialize();
        } catch (ClientExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface | BadRequestHttpException $exception) {
            return false;
        }

        $request->attributes->set($param, $user);

        return true;
    }

    /**
     * Checks if the object is supported.
     *
     * @param ParamConverter $configuration
     * @return bool True if the object is supported, else false
     */
    public function supports(ParamConverter $configuration)
    {
        if (null === $configuration->getClass()) {
            return false;
        }

        return $configuration->getClass() === User::class;
    }
}