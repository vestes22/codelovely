<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions\Contracts;

/**
 * Exception contract.
 *
 * @since 3.4.1
 */
interface ExceptionContract
{
    /**
     * Constructor accepting a message passed in by the specific use case.
     *
     * @since 3.4.1
     *
     * @param string $message
     */
    public function __construct(string $message);

    /**
     * Contains the logic and functionality to complete when the Exception has finished processing.
     *
     * @since 3.4.1
     *
     * @return mixed|void
     */
    public function callback();

    /**
     * Gets the exception error code.
     *
     * @since 3.4.1
     *
     * @return mixed
     */
    public function getCode();

    /**
     * Gets the context included as an array with the Exception.
     *
     * @since 3.4.1
     *
     * @return array
     */
    public function getContext() : array;

    /**
     * Gets the file in which the exception occurred.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getFile();

    /**
     * Gets the error level of the exception.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getLevel() : string;

    /**
     * Gets the line on which the exception occurred.
     *
     * @since 3.4.1
     *
     * @return int
     */
    public function getLine();

    /**
     * Gets the exception error message.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getMessage();
}
