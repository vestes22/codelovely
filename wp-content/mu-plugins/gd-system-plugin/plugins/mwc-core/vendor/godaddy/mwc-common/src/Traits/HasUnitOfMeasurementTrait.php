<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

/**
 * A trait to handle units of measurement.
 *
 * @since 3.4.1
 */
trait HasUnitOfMeasurementTrait
{
    /** @var string|null */
    protected $unit;

    /**
     * Gets the unit of measurement.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getUnitOfMeasurement() : string
    {
        return is_string($this->unit) ? $this->unit : '';
    }

    /**
     * Sets the unit of measurement.
     *
     * @since 3.4.1
     *
     * @param string $unit
     * @return self
     */
    public function setUnitOfMeasurement(string $unit)
    {
        $this->unit = $unit;

        return $this;
    }
}
