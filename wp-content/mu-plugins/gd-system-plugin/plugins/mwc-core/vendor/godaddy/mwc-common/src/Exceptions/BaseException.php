<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\Contracts\ExceptionContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\Handlers\BaseExceptionHandler;

/**
 * Base exception class, to be extended by our exceptions.
 *
 * @since 3.4.1
 */
class BaseException extends Exception implements ExceptionContract
{
    /** @var int exception code */
    protected $code = 500;

    /** @var string exception level */
    protected $level = 'error';

    /** @var BaseExceptionHandler exception handler */
    protected $handler;

    /**
     * Constructor.
     *
     * @since 3.4.1
     *
     * @param string $message exception message
     * @throws Exception
     */
    public function __construct(string $message)
    {
        $this->handler = $this->getExceptionHandler();

        $this->handler->registerHandler();

        parent::__construct($message, $this->code);
    }

    /**
     * Deconstruct.
     *
     * @since 3.4.1
     */
    public function __destruct()
    {
        $this->handler->deregisterHandler();
    }

    /**
     * Adds an exception callback.
     *
     * @NOTE Allow exceptions to define a callback so that they may determine specific actions to take place following an exception of a certain type.
     *
     * @since 3.4.1
     */
    public function callback()
    {
    }

    /**
     * Gets the default context to be included with the exception.
     *
     * @NOTE This allows us to ensure certain context is always included for exceptions or reporting.  Keep in mind that an exception inheriting this class may override this context with its over method.
     *
     * @since 3.4.1
     *
     * @return array
     * @throws Exception
     */
    public function getContext() : array
    {
        return [
            'account'  => Configuration::get('godaddy.account.uid'),
            'cdn'      => Configuration::get('godaddy.cdn'),
            'site_url' => Configuration::get('mwc.url'),
            'versions' => [
                'mwc'         => Configuration::get('mwc.version'),
                'woocommerce' => Configuration::get('woocommerce.version'),
                'wordpress'   => Configuration::get('wordpress.version'),
            ],
        ];
    }

    /**
     * Gets the handler assigned to this exception.
     *
     * @NOTE We do not want to allow an exception to switch the handlers it flows through.
     * An exception should have predictable behavior or a new explicit exception type should be created.
     * As such we provide an overrideable method for setting the handler.
     *
     * @since 3.4.1
     *
     * @return BaseExceptionHandler
     * @throws Exception
     */
    protected function getExceptionHandler() : BaseExceptionHandler
    {
        return new BaseExceptionHandler();
    }

    /**
     * Gets the exception level.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }
}
