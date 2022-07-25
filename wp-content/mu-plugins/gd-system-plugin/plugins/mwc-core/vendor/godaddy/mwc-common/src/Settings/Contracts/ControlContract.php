<?php

namespace GoDaddy\WordPress\MWC\Common\Settings\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;

/**
 * An interface for object representations of setting controls.
 */
interface ControlContract extends HasLabelContract
{
    /**
     * Gets the control ID.
     *
     * @return string|null
     */
    public function getId();

    /**
     * Gets the control type.
     *
     * @return string|null
     */
    public function getType();

    /**
     * Gets the control description.
     *
     * @return string
     */
    public function getDescription() : string;

    /**
     * Gets the control options.
     *
     * @return array
     */
    public function getOptions() : array;

    /**
     * Gets the control placeholder, if available.
     *
     * @return mixed|null
     */
    public function getPlaceholder();

    /**
     * Sets the control ID.
     *
     * @param string $value
     * @return self
     */
    public function setId(string $value);

    /**
     * Sets the control type.
     *
     * @param string $value
     * @return self
     */
    public function setType(string $value);

    /**
     * Sets the control description.
     *
     * @param string $value
     * @return self
     */
    public function setDescription(string $value);

    /**
     * Sets the control options.
     *
     * @param array $value
     * @return self
     */
    public function setOptions(array $value);

    /**
     * Sets the control placeholder.
     *
     * @param mixed|null $value
     * @return self
     */
    public function setPlaceholder($value);
}
