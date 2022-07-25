<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

class ButtonClickedEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var string The ID of the call to action */
    protected $id;

    /**
     * Constructor.
     *
     * @param string $id call to action ID
     */
    public function __construct(string $id)
    {
        $this->resource = 'button';
        $this->action = 'click';
        $this->id = $id;
    }

    /**
     * Gets the data for the event.
     *
     * @return array
     */
    public function getData(): array
    {
        return [
            'button' => [
                'id' => $this->id,
            ],
        ];
    }
}
