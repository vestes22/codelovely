<?php

namespace GoDaddy\WordPress\MWC\Common\Contracts;

/**
 * Label contract interface.
 *
 * @since 3.4.1
 */
interface HasLabelContract
{
    /**
     * Gets the label name.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Gets the label value.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getLabel() : string;

    /**
     * Sets the label name.
     *
     * @since 3.4.1
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name);

    /**
     * Sets the label value.
     *
     * @since 3.4.1
     *
     * @param string $label
     * @return self
     */
    public function setLabel(string $label);
}
