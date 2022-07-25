<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

/**
 * Feature disabled event class.
 *
 * @since 2.10.0
 */
class FeatureDisabledEvent extends AbstractFeatureEvent
{
    /**
     * FeatureDisabledEvent constructor.
     *
     * @since 2.10.0
     *
     * @param string $featureId
     */
    public function __construct(string $featureId)
    {
        parent::__construct($featureId);

        $this->action = 'disable';
    }
}
