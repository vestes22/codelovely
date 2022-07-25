<?php

namespace GoDaddy\WordPress\MWC\Common\Helpers;

/**
 * Comparison helper.
 *
 * @since 1.0.0
 */
class ComparisonHelper
{
    /** @var string the equals operator */
    const EQUALS = 'eq';

    /** @var string the in operator */
    const IN = 'in';

    /** @var mixed the value to be checked */
    private $value;

    /** @var string the operator */
    private $operator;

    /** @var mixed the object to check against value */
    private $with;

    /** @var bool determines whether the string comparisons will be case sensitive */
    private $caseSensitive = true;

    /**
     * Sets the value to be compared with something.
     *
     * @since 1.0.0
     *
     * @param mixed $value
     *
     * @return ComparisonHelper
     */
    public function setValue($value) : ComparisonHelper
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Sets the comparison operator.
     *
     * @since 1.0.0
     *
     * @param string $operator
     *
     * @return ComparisonHelper
     */
    public function setOperator(string $operator) : ComparisonHelper
    {
        $this->operator = $operator;

        $this->normalizeOperator();

        return $this;
    }

    /**
     * Sets the object to check against value.
     *
     * @since 1.0.0
     *
     * @param mixed $with
     *
     * @return ComparisonHelper
     */
    public function setWith($with) : ComparisonHelper
    {
        $this->with = $with;

        return $this;
    }

    /**
     * Sets the case sensitive comparison flag.
     *
     * @since 1.0.0
     *
     * @param bool $caseSensitive false if string comparisons must not be case sensitive
     *
     * @return ComparisonHelper
     */
    public function setCaseSensitive(bool $caseSensitive) : ComparisonHelper
    {
        $this->caseSensitive = $caseSensitive;

        return $this;
    }

    /**
     * Performs the comparison.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function compare() : bool
    {
        $this->value = $this->normalize($this->value);
        $this->with = $this->normalize($this->with);

        switch ($this->operator) {
            case self::EQUALS:
                return $this->equals();

            case self::IN:
                return $this->in();
        }

        return false;
    }

    /**
     * Determines whether the values are equal.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function equals() : bool
    {
        return $this->value === $this->with;
    }

    /**
     * Determines whether the value is in an array.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    private function in() : bool
    {
        return ArrayHelper::accessible($this->with) && in_array($this->value, $this->with, true);
    }

    /**
     * Normalizes the operator when necessary.
     *
     * Given that ComparisonHelper may allow setting any operator (that also may be unknown in the development stage),
     * some variations are allowed but must be normalized to a defined const.
     *
     * @since 1.0.0
     */
    private function normalizeOperator()
    {
        if (in_array($this->operator, ['equals', 'equal', '='])) {
            $this->operator = static::EQUALS;
        }
    }

    /**
     * Normalizes all values if necessary.
     *
     * @since 1.0.0
     *
     * @param mixed $value the value to be normalized
     *
     * @return mixed
     */
    private function normalize($value)
    {
        if (! $this->caseSensitive) {
            if (is_string($value)) {
                return strtolower($value);
            }

            if (is_array($value)) {
                return array_map(static function ($arrayValue) {
                    return is_string($arrayValue) ? strtolower($arrayValue) : $arrayValue;
                }, $value);
            }
        }

        return $value;
    }

    /**
     * Creates a ComparisonHelper instance.
     *
     * The result of this method is the same as calling its constructor, however, create() allows chaining calls easily.
     *
     * @since 1.0.0
     *
     * @return ComparisonHelper
     */
    public static function create() : ComparisonHelper
    {
        return new self;
    }
}
