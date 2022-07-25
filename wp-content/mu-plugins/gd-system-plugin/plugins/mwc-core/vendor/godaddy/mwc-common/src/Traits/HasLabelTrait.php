<?php

namespace GoDaddy\WordPress\MWC\Common\Traits;

/**
 * A trait to handle labels.
 *
 * @since 3.4.1
 */
trait HasLabelTrait
{
    /** @var string|null */
    protected $name;

    /** @var string|null */
    protected $label;

    /**
     * Gets the label name.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getName() : string
    {
        return is_string($this->name) ? $this->name : '';
    }

    /**
     * Gets the label value.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getLabel() : string
    {
        return is_string($this->label) ? $this->label : '';
    }

    /**
     * Sets the label name.
     *
     * @since 3.4.1
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the label value.
     *
     * @since 3.4.1
     *
     * @param string $label
     * @return self
     */
    public function setLabel(string $label)
    {
        $this->label = $label;

        return $this;
    }
}
