<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Request;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Traits\MasksData;
use GoDaddy\WordPress\MWC\Payments\Events\ProviderRequestEvent;

/**
 * Class RequestLogSubscriber.
 */
class RequestDebugNoticeSubscriber extends AbstractDebugNoticeSubscriber
{
    use MasksData;

    /**
     * Gets the given body data array formatted for display in notices.
     *
     * @param array $body
     *
     * @return string
     */
    protected function getFormattedBody(array $body) : string
    {
        return htmlspecialchars(ArrayHelper::jsonEncode($body));
    }

    /**
     * Gets the given headers array formatted for display in notices.
     *
     * @param array $headers
     *
     * @return string
     */
    protected function getFormattedHeaders(array $headers) : string
    {
        return str_replace("\n", '<br />', htmlspecialchars(print_r($headers, true)));
    }

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

        $headers = $this->getFormattedHeaders($this->maskData($request->headers ?? [], ['Authorization']));
        $body = $this->getFormattedBody($this->maskData($request->body ?? [], ['assertion']));

        return "Request<br />URL: {$request->url}<br />Method: {$request->method}<br />Headers: {$headers}Body:<br />{$body}";
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
        return
            parent::shouldHandle($event)
            && $event instanceof ProviderRequestEvent
            && $event->getRequest() instanceof Request;
    }
}
