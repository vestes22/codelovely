<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Contracts;

/**
 * Model contract.
 *
 * @since 3.4.1
 */
interface ModelContract
{
    /**
     * Creates a new instance of the given model class and saves it.
     *
     * @since 3.4.1
     *
     * @return self
     */
    public static function create();

    /**
     * Gets an instance of the given model class, if found.
     *
     * @since 3.4.1
     *
     * @param mixed $identifier
     * @return self|null
     */
    public static function get($identifier);

    /**
     * Updates a given instance of the model class.
     *
     * @since 3.4.1
     *
     * @return self
     */
    public function update();

    /**
     * Updates a given instance of the model class.
     *
     * @since 3.4.1
     */
    public function delete();

    /**
     * Saves the instance of the class with its current state.
     *
     * @since 3.4.1
     *
     * @return self
     */
    public function save();

    /**
     * Seeds an instance of the given model class without saving it.
     *
     * @since 3.4.1
     *
     * @return self
     */
    public static function seed();

    /**
     * Converts all class data properties to an array.
     *
     * @return array
     */
    public function toArray() : array;
}
