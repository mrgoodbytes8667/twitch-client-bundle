<?php


namespace Bytes\TwitchClientBundle\EventListener;


use Bytes\TwitchClientBundle\Event\EventSubSubscriptionGenerateCallbackEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class EventSubSubscriptionGenerateCallbackSubscriber
 * @package Bytes\TwitchClientBundle\EventListener
 */
class EventSubSubscriptionGenerateCallbackSubscriber implements EventSubscriberInterface
{
    /**
     * EventSubSubscriptionGenerateCallbackSubscriber constructor.
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(protected UrlGeneratorInterface $urlGenerator)
    {
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName', $priority]]
     *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * The code must not depend on runtime state as it will only be called at compile time.
     * All logic depending on runtime state must be put into the individual methods handling the events.
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EventSubSubscriptionGenerateCallbackEvent::class => ['onGenerateCallback', -1],
        ];
    }

    /**
     * @param EventSubSubscriptionGenerateCallbackEvent $event
     * @return EventSubSubscriptionGenerateCallbackEvent
     */
    public function onGenerateCallback(EventSubSubscriptionGenerateCallbackEvent $event): EventSubSubscriptionGenerateCallbackEvent
    {
        if($event->isGenerationSkipped() && $event->hasUrl())
        {
            $event->stopPropagation();
            return $event;
        }
        
        $url = $this->urlGenerator->generate($event->getCallbackName(), $event->getParameters(), $event->getReferenceType());
        $event->setUrl($url);
        return $event;
    }
}