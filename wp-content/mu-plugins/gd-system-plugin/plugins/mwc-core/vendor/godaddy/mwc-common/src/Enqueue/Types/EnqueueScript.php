<?php

namespace GoDaddy\WordPress\MWC\Common\Enqueue\Types;

use Exception;
use GoDaddy\WordPress\MWC\Common\Enqueue\Contracts\EnqueuableContract;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;

/**
 * Script enqueueable.
 *
 * @since 1.0.0
 */
final class EnqueueScript extends Enqueue implements EnqueuableContract
{
    /** @var string|null optional JavaScript object name to be added inline after successful enqueue */
    protected $scriptObject;

    /** @var array optional JavaScript object variables to be added inline */
    protected $scriptVariables = [];

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->setType('script');
    }

    /**
     * Instructs to add an inline JavaScript object with a name.
     *
     * @since 1.0.0
     *
     * @param string $objectName
     * @return self
     */
    public function attachInlineScriptObject(string $objectName) : self
    {
        $this->scriptObject = $objectName;

        return $this;
    }

    /**
     * Adds script variables to the inline JavaScript object, if set.
     *
     * @since 1.0.0
     *
     * @param array $variables associative array
     * @return $this
     */
    public function attachInlineScriptVariables(array $variables) : self
    {
        $this->scriptVariables = array_merge($this->scriptVariables, $variables);

        return $this;
    }

    /**
     * Loads the script in WordPress.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function execute()
    {
        $this->validate();
        $this->register();
        $this->enqueue();
    }

    /**
     * Registers the asset in WordPress.
     *
     * @since 1.0.0
     */
    private function register()
    {
        wp_register_script(
            $this->handle,
            $this->source,
            $this->dependencies,
            $this->version,
            $this->deferred
        );
    }

    /**
     * Enqueues the script in WordPress.
     *
     * @since 1.0.0
     */
    private function enqueue()
    {
        if (! $this->shouldEnqueue()) {
            return;
        }

        wp_enqueue_script($this->handle);

        if ($this->scriptObject) {
            wp_localize_script(
                $this->handle,
                $this->scriptObject,
                $this->scriptVariables
            );
        }
    }

    /**
     * Validates the current instance.
     *
     * @since 1.0.0
     *
     * @throws Exception
     */
    public function validate()
    {
        if (! $this->handle) {
            throw new Exception('You must provide a handle name for the script to be enqueued.');
        }

        if (! $this->source) {
            throw new Exception("You must provide a URL to enqueue the script `{$this->handle}`.");
        }

        if (! function_exists('wp_register_script')) {
            throw new Exception("Cannot register the script `{$this->handle}`: the function `wp_register_script()` does not exist.");
        }

        if ($this->scriptObject && ! function_exists('wp_localize_script')) {
            throw new Exception("Cannot add an inline script object `{$this->scriptObject}` for the script `{$this->handle}`: the function `wp_localize_script()` does not exist.");
        }

        if (! function_exists('wp_enqueue_script')) {
            throw new Exception("Cannot enqueue the script `{$this->handle}`: the function `wp_enqueue_script()` does not exist.");
        }
    }
}
