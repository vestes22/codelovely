<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions\Contracts;

use ErrorException;
use Exception;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use Throwable;

/**
 * Exception handler contract.
 *
 * @since 3.4.1
 */
interface ExceptionHandlerContract
{
    /**
     * Deregisters the handler.
     *
     * @NOTE: Must restore the previous handlers.
     *
     * @since 3.4.1
     */
    public function deregisterHandler();

    /**
     * Initializes the handler.
     *
     * @NOTE: Must contain {@see set_exception_handler()} and {@see set_error_handler()}.
     *
     * @since 3.4.1
     */
    public function registerHandler();

    /**
     * The default method that handles PHP errors.
     *
     * @since 3.4.1
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0);

    /**
     * The default method that handles PHP exceptions.
     *
     * @since 3.4.1
     *
     * @param Throwable $exception
     * @throws Exception|Throwable
     */
    public function handleException(Throwable $exception);

    /**
     * Adds an exception class name to the ignore list.
     *
     * @since 3.4.1
     *
     * @param string $class exception class
     * @return self
     */
    public function ignore(string $class);

    /**
     * Method that actually reports the error.
     *
     * @since 3.4.1
     *
     * @param BaseException $exception
     * @throws Exception
     */
    public function report(BaseException $exception);
}
