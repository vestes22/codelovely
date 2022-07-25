<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Events\AbstractWebhookReceivedEvent;

class WebhookEventsProducer implements ProducerContract
{
    /**
     * Sets up the Coupon events producer.
     *
     * @deprecated
     *
     * @throws Exception
     */
    public function setup()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '2.18.1', __CLASS__.'::load');

        $this->load();
    }

    /**
     * Loads the component.
     *
     * @throws Exception
     */
    public function load()
    {
        foreach (Configuration::get('webhooks', []) as $webhookData) {
            $namespace = ArrayHelper::get($webhookData, 'namespace');

            if ('' === $namespace || ! is_string($namespace)) {
                continue;
            }

            Register::action()
                ->setGroup("woocommerce_api_{$namespace}")
                ->setHandler([$this, 'broadcastEvents'])
                ->execute();
        }
    }

    /**
     * Broadcasts an event when a request is received for a given webhook namespace.
     *
     * This is a callback for WooCommerce API actions set in {@see WebhookEventsProducer::load()}.
     *
     * @internal
     *
     * @throws Exception
     */
    public function broadcastEvents()
    {
        $hook = current_action();

        foreach (Configuration::get('webhooks', []) as $webhookData) {
            $namespace = ArrayHelper::get($webhookData, 'namespace');
            $eventClass = ArrayHelper::get($webhookData, 'eventClass');

            if (! $this->shouldBroadcast($eventClass) || ! StringHelper::contains($hook, $namespace)) {
                continue;
            }

            /** @var AbstractWebhookReceivedEvent $event */
            $event = new $eventClass(
                $this->getRequestHeaders(),
                $this->getRequestPayload()
            );

            Events::broadcast($event);
        }
    }

    /**
     * Gets the request headers.
     *
     * @return array
     */
    protected function getRequestHeaders() : array
    {
        return ArrayHelper::where(ArrayHelper::wrap($_SERVER), function ($value, $key) {
            return StringHelper::startsWith($key, 'HTTP_') || StringHelper::startsWith($key, 'CONTENT_');
        });
    }

    /**
     * Gets the request payload.
     *
     * @return string
     */
    protected function getRequestPayload() : string
    {
        return file_get_contents('php://input') ?: '';
    }

    /**
     * Determines whether an event should be broadcast.
     *
     * @param string|mixed $eventClass
     * @return bool
     */
    private function shouldBroadcast($eventClass) : bool
    {
        return is_string($eventClass) && class_exists($eventClass) && is_subclass_of($eventClass, AbstractWebhookReceivedEvent::class);
    }
}
