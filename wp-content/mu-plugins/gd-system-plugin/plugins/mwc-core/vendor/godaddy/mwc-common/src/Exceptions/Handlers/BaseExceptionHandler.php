<?php

namespace GoDaddy\WordPress\MWC\Common\Exceptions\Handlers;

use ErrorException;
use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\BaseException;
use GoDaddy\WordPress\MWC\Common\Exceptions\Contracts\ExceptionHandlerContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Loggers\Logger;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Exceptions handler.
 */
class BaseExceptionHandler implements ExceptionHandlerContract
{
    /**
     * Array of exception names to not report.
     *
     * @NOTE The exceptions stored in this property are those that should be ignored when thrown.
     * These may be items we want to raise and trigger some special handling, but don't want it to permeate to a full exception.
     * E.g. We want to send specific analytics information to an internal aggregator or log a report don't want to raise a full exception.
     * A more simplistic example is that we no longer care about a particular exception but can not confidently remove it from the code without breaking backwards compatibility.
     *
     * @var array
     */
    protected $dontReport = [];

    /**
     * Remove the registered handler.
     *
     * @NOTE We have to restore the previous handler so that exceptions which
     * aren't ours flow through as expected.
     */
    public function deregisterHandler()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Initialize the handler.
     *
     * @throws Exception
     */
    public function registerHandler()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);

        /*
         * All exceptions using this handler should have their own error reporting workflow.
         * Exceptions not using this handler will function as normal.
         */
        if (! ManagedWooCommerceRepository::isTestingEnvironment()) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Convert the given exception to an array.
     *
     * @NOTE This is useful if we want to deliver a json response which would be useful for rendering to the end user if we choose to split rendering and reporting on a given exception type or move to do that across the board in this base class.
     *
     * @param BaseException $exception
     * @return array
     * @throws Exception
     */
    protected function convertExceptionToArray(BaseException $exception) : array
    {
        if (! ManagedWooCommerceRepository::isProductionEnvironment() || Configuration::get('mwc.debug')) {
            return [
                'message'   => $this->getExceptionMessage($exception),
                'exception' => get_class($exception),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'trace'     => $this->getExceptionStackTrace($exception),
            ];
        }

        return [
            'message' => $this->isHttpResponse() ? $this->getExceptionMessage($exception) : 'Server Error',
        ];
    }

    /**
     * Gets the default context to be included with the exception.
     *
     * @NOTE This allows us to ensure certain context is always included for exceptions or reporting.  Keep in mind that an exception inheriting this class may override this context with its over method.
     *
     * @param BaseException $exception
     * @return array
     */
    protected function getContext(BaseException $exception) : array
    {
        try {
            return $exception->getContext();
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Gets the stack trace for an exception.
     *
     * @NOTE We want to remove the args from each stack trace entry to keep things condensed and protect any sensitive information.
     *
     * @param BaseException $exception
     * @return array stack trace
     */
    protected function getExceptionStackTrace(BaseException $exception) : array
    {
        $stack = [];

        foreach ($exception->getTrace() as $trace) {
            $stack[] = ArrayHelper::except($trace, 'args');
        }

        return $stack;
    }

    /**
     * Gets the exception message.
     *
     * Allow Exceptions to overwrite the message which will be displayed.
     *
     * @param BaseException $exception
     * @return string exception message
     */
    public function getExceptionMessage(BaseException $exception) : string
    {
        return $exception->getMessage();
    }

    /**
     * Handles errors.
     *
     * Converts PHP errors to {@see ErrorException} instances.
     *
     * @NOTE Errors are handled differently in PHP 7+ so we should convert them into an exception instance then handle via the normal workflow.
     *
     * @param int $level
     * @param string $message
     * @param string $file
     * @param int $line
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0)
    {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handles the actual exceptions.
     *
     * @NOTE If we later want to handle the actual exception and what is reported/rendered to the end user then this is the place to do it.
     * E.g. We could allow a different "rendered message" from the more detailed exception message.
     *
     * @param Throwable $exception
     * @throws Exception|Throwable
     */
    public function handleException(Throwable $exception)
    {
        if (! $exception instanceof BaseException) {
            throw $exception;
        }

        if ($this->shouldIgnore($exception)) {
            return;
        }

        // perform the callback defined by the exception
        $exception->callback();

        $this->report($exception);
    }

    /**
     * Ignores an exception.
     *
     * Set a specific exception to not be reported.
     *
     * @param string $class exception class
     * @return self
     */
    public function ignore(string $class) : BaseExceptionHandler
    {
        $this->dontReport[] = $class;

        return $this;
    }

    /**
     * Determines if the current is a HTTP response.
     *
     * @NOTE When this is not an HTTP response certain information will be hidden.
     * E.g. In CLI mode we do not want to ever display the stack trace etc unless we are specifically in debug mode.
     *
     * @return bool
     * @throws Exception
     */
    protected function isHttpResponse() : bool
    {
        return ! WordPressRepository::isCliMode();
    }

    /**
     * Logs the exception using {@see LoggerInterface}.
     *
     * @param BaseException $exception the exception
     * @param string $level exception level
     * @throws Exception
     */
    protected function log(BaseException $exception, string $level = 'error')
    {
        try {
            $this->getLogger()->log(
                $level,
                $this->getExceptionMessage($exception),
                ArrayHelper::combine($this->getContext($exception), ['exception' => $exception])
            );
        } catch (Exception $failed) {
            throw $failed;
        }
    }

    /**
     * Gets a Logger instance.
     *
     * Classes extending this handler can use alternative loggers, if desired.
     *
     * @return Logger
     */
    protected function getLogger() : Logger
    {
        return new Logger();
    }

    /**
     * Reports an exception.
     *
     * @param BaseException $exception
     * @throws Exception
     */
    public function report(BaseException $exception)
    {
        // log the actual exception
        $this->log($exception, $exception->getLevel());
    }

    /**
     * Determines if the exception should be ignored.
     *
     * @param BaseException $exception
     * @return bool
     */
    protected function shouldIgnore(BaseException $exception) : bool
    {
        return ArrayHelper::contains($this->dontReport, get_class($exception));
    }
}
