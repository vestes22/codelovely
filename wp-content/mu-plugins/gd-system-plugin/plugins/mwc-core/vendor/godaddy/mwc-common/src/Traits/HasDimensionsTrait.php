<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

use GoDaddy\WordPress\MWC\Common\Models\Dimensions;

/**
 * A trait to assign dimension properties to an object.
 *
 * @since 3.4.1
 */
trait HasDimensionsTrait
{
    /** @var Dimensions */
    private $dimensions;

    /**
     * Gets the dimensions.
     *
     * @since 3.4.1
     *
     * @return Dimensions
     */
    public function getDimensions() : Dimensions
    {
        return $this->dimensions;
    }

    /**
     * Sets the dimensions.
     *
     * @since 3.4.1
     *
     * @param Dimensions $dimensions
     * @return self
     */
    public function setDimensions(Dimensions $dimensions)
    {
        $this->dimensions = $dimensions;

        return $this;
    }
}
