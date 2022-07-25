<?php

namespace GoDaddy\WordPress\MWC\Payments\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Http\Response;

/**
 * Class ProviderResponseEvent.
 *
 * @since 0.1.0
 */
class ProviderResponseEvent implements EventContract
{
    /** @var Response */
    protected $response;

    /**
     * ProviderResponseEvent constructor.
     *
     * @since 0.1.0
     *
     * @param Response $response the response that fired this event
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Gets the response that fired this event.
     *
     * @since 0.1.0
     *
     * @return Response
     */
    public function getResponse() : Response
    {
        return $this->response;
    }
}
