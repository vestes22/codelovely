<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

/**
 * Feature enabled event class.
 *
 * @since 2.10.0
 */
class FeatureEnabledEvent extends AbstractFeatureEvent
{
    /**
     * FeatureEnabledEvent constructor.
     *
     * @since 2.10.0
     *
     * @param string $featureId
     */
    public function __construct(string $featureId)
    {
        parent::__construct($featureId);

        $this->action = 'enable';
    }
}
