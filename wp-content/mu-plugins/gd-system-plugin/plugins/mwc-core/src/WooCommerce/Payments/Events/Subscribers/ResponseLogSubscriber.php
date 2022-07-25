<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Http\Response;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Traits\MasksData;
use GoDaddy\WordPress\MWC\Payments\Events\ProviderResponseEvent;

/**
 * Class ResponseLogSubscriber.
 *
 * TODO: this can definitely be further abstracted to handle any request, not just payments {@cwiseman 2021-05-23}
 */
class ResponseLogSubscriber extends AbstractLogSubscriber
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
        /** @var Response $request */
        $response = $event->getResponse();

        $body = print_r($this->maskData($response->body ?? [], [
            'accessToken',
            'refreshToken',
        ]), true);

        return "Response\nBody: {$body}";
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
        return $event instanceof ProviderResponseEvent && ArrayHelper::contains(['log', 'both'], Configuration::get('payments.poynt.debugMode'));
    }
}
