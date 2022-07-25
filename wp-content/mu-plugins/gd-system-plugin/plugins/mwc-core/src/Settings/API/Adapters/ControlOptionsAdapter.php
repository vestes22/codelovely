<?php

namespace GoDaddy\WordPress\MWC\Core\Settings\API\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;

/* Adapts options key-value array from a settings Control to a format suitable for API responses. */
class ControlOptionsAdapter implements DataSourceAdapterContract
{
    /* @var array */
    protected $source;

    /**
     * Initializes adapter.
     *
     * @param array $source an assoc. array with [value => label, value => label] structure.
     */
    public function __construct(array $source = [])
    {
        $this->source = $source;
    }

    /**
     * Maps the source associative array of control options to an array of assoc. arrays with keys label and value.
     *
     * @return array[] an array of assoc. arrays like [['label' => 'City', 'value' => 'city']]
     */
    public function convertFromSource(): array
    {
        return array_map(function ($value, $label) {
            return ['label' => $label, 'value' => $value];
        }, array_keys($this->source), array_values($this->source));
    }

    /**
     * Maps a given array of assoc. arrays with keys label and value to the source format value-label associative array.
     *
     * @param array[] $options an array of assoc. arrays like [['label' => 'City', 'value' => 'city']]
     * @return array assoc. array of value-label options.
     */
    public function convertToSource(array $options = []): array
    {
        $this->source = array_reduce($options, function (array $carry, array $option) {
            $carry[$option['value']] = $option['label'];

            return $carry;
        }, []);

        return $this->source;
    }
}
