<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Request;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Traits\MasksData;
use GoDaddy\WordPress\MWC\Payments\Events\ProviderRequestEvent;

/**
 * Class RequestLogSubscriber.
 *
 * TODO: this can definitely be further abstracted to handle any request, not just payments {@cwiseman 2021-05-23}
 */
class RequestLogSubscriber extends AbstractLogSubscriber
{
    use MasksData;

    /** @var string log ID */
    protected $id = 'godaddy-payments';

    /**
     * Gets a message to log from the given event.
     *
     * @param EventContract $event
     *
     * @return string
     */
    protected function getMessage(EventContract $event) : string
    {
        /** @var Request $request */
        $request = $event->getRequest();

        $headers = print_r($this->maskData($request->headers ?? [], ['Authorization']), true);
        $body = print_r($this->maskData($request->body ?? [], ['assertion']), true);

        return "Request\nURL: {$request->url}\nMethod: {$request->method}\nHeaders: {$headers}\nBody: {$body}";
    }

    /**
     * Determines whether the event should be handled.
     *
     * @param EventContract $event
     *
     * @return bool
     * @throws Exception
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        return $event instanceof ProviderRequestEvent && $event->getRequest() instanceof Request && ArrayHelper::contains(['log', 'both'], Configuration::get('payments.poynt.debugMode'));
    }
}
