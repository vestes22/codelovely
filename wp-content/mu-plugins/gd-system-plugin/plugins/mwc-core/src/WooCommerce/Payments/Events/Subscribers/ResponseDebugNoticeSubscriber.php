<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Traits\MasksData;
use GoDaddy\WordPress\MWC\Payments\Events\ProviderResponseEvent;

/**
 * Class RequestLogSubscriber.
 */
class ResponseDebugNoticeSubscriber extends AbstractDebugNoticeSubscriber
{
    use MasksData;

    /**
     * Gets a message to log from the given event.
     *
     * @param EventContract $event
     *
     * @return string
     */
    protected function getMessage(EventContract $event) : string
    {
        /** @var Response $request */
        $response = $event->getResponse();

        $body = htmlspecialchars(ArrayHelper::jsonEncode($this->maskData($response->body ?? [], [
            'accessToken',
            'refreshToken',
        ])));

        return "Response<br />Body:<br />{$body}";
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
            && $event instanceof ProviderResponseEvent
            && $event->getResponse() instanceof Response;
    }
}
