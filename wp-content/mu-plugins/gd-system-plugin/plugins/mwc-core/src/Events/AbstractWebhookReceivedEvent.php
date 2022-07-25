<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;

/**
 * Abstract webhook received event class.
 */
abstract class AbstractWebhookReceivedEvent implements EventContract
{
    /** @var array */
    protected $headers;

    /** @var string */
    protected $payload;

    /**
     * Event constructor.
     *
     * @param array $headers
     * @param string $payload
     */
    public function __construct(array $headers, string $payload)
    {
        $this->headers = $headers;
        $this->payload = $payload;
    }

    /**
     * Gets the headers.
     *
     * @return array
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * Gets the payload.
     *
     * @return string
     */
    public function getPayload() : string
    {
        return $this->payload;
    }

    /**
     * Gets the JSON payload as a decoded array.
     *
     * @return array
     */
    public function getPayloadDecoded() : array
    {
        return json_decode($this->getPayload(), true);
    }
}
