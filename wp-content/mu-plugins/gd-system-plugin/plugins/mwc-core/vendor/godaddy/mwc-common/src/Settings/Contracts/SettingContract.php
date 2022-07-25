<?php

namespace GoDaddy\WordPress\MWC\Common\Settings\Contracts;

use GoDaddy\WordPress\MWC\Common\Contracts\HasLabelContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;

/**
 * An interface for object representations of a setting.
 */
interface SettingContract extends ModelContract, HasLabelContract
{
    /**
     * Gets the setting ID.
     *
     * @return string
     */
    public function getId() : string;

    /**
     * Gets the setting name.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Gets the setting label.
     */
    public function getLabel() : string;

    /**
     * Gets the setting type.
     *
     * @return string
     */
    public function getType() : string;

    /**
     * Gets the setting description.
     *
     * @return string
     */
    public function getDescription() : string;

    /**
     * Gets the setting options.
     *
     * @return array
     */
    public function getOptions() : array;

    /**
     * Gets the setting default value.
     *
     * @return int|float|string|bool|array
     */
    public function getDefault();

    /**
     * Gets the setting value.
     *
     * @return int|float|string|bool|array
     */
    public function getValue();

    /**
     * Gets the setting control.
     *
     * @return ControlContract
     */
    public function getControl() : ControlContract;

    /**
     * Determines whether the setting is multivalued.
     *
     * @return bool
     */
    public function isMultivalued() : bool;

    /**
     * Determines whether the setting is required.
     *
     * @return bool
     */
    public function isRequired() : bool;

    /**
     * Determines whether the setting has a value.
     *
     * @return bool
     */
    public function hasValue() : bool;

    /**
     * Sets the setting ID.
     *
     * @param string $value
     * @return self
     */
    public function setId(string $value);

    /**
     * Sets the setting name.
     *
     * @param string $value
     * @return self
     */
    public function setName(string $value);

    /**
     * Sets the setting label.
     *
     * @param string $value
     * @return self
     */
    public function setLabel(string $value);

    /**
     * Sets the setting type.
     *
     * @param string $value
     * @return self
     */
    public function setType(string $value);

    /**
     * Sets the setting description.
     *
     * @param string $value
     * @return self
     */
    public function setDescription(string $value);

    /**
     * Sets the setting options.
     *
     * @param array $value
     * @return self
     */
    public function setOptions(array $value);

    /**
     * Sets the setting default value.
     *
     * @param int|float|string|bool|array $value
     * @return self
     */
    public function setDefault($value);

    /**
     * Sets the setting value.
     *
     * @since 3.4.1
     *
     * @param int|float|string|bool|array $value
     * @return self
     */
    public function setValue($value);

    /**
     * Clears the setting value.
     *
     * @return self
     */
    public function clearValue();

    /**
     * Sets whether the setting is multivalued.
     *
     * @param bool $value
     * @return self
     */
    public function setIsMultivalued(bool $value);

    /**
     * Sets the setting control.
     *
     * @param ControlContract $value
     * @return self
     */
    public function setControl(ControlContract $value);
}
