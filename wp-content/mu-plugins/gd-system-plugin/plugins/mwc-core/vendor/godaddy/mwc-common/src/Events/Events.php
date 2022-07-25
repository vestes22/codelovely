<?php

namespace GoDaddy\WordPress\MWC\Common\Events;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;

/**
 * Event Handler.
 */
class Events
{
    /**
     * Broadcast one or more events.
     *
     * @param EventContract|EventContract[] $events an array of events
     */
    public static function broadcast($events)
    {
        foreach (ArrayHelper::wrap($events) as $event) {
            static::broadcastEvent($event);
        }
    }

    /**
     * Broadcast an event.
     *
     * @TODO: Add queue support here if the Event has a queuable trait {JO: 2021-03-19}
     *
     * @param EventContract $event
     */
    protected static function broadcastEvent(EventContract $event)
    {
        foreach (static::getSubscribers($event) as $subscriber) {
            (new $subscriber())->handle($event);
        }
    }

    /**
     * Gets a list of subscribers.
     *
     * Returns for a given event if provided or all events if none is provided.
     *
     * @param EventContract $event
     * @return string[] array of class names
     */
    public static function getSubscribers(EventContract $event) : array
    {
        $listeners = Configuration::get('events.listeners');

        return ArrayHelper::get($listeners, get_class($event), []);
    }

    /**
     * Check if a given event has a given subscriber.
     *
     * @param EventContract $event
     * @param SubscriberContract $subscriber
     *
     * @return bool
     */
    public static function hasSubscriber(EventContract $event, SubscriberContract $subscriber) : bool
    {
        return ArrayHelper::contains(static::getSubscribers($event), get_class($subscriber));
    }
}
