<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Abstract feature event class.
 */
abstract class AbstractFeatureEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var string */
    protected $featureId;

    /**
     * AbstractFeatureEvent constructor.
     *
     * @param string $featureId
     */
    public function __construct(string $featureId)
    {
        $this->featureId = $featureId;
        $this->resource = 'feature';
    }

    /**
     * Gets the data for the current event.
     *
     * @return array
     */
    public function getData() : array
    {
        return [
            'feature' => [
                'id' => $this->featureId,
            ],
        ];
    }
}
