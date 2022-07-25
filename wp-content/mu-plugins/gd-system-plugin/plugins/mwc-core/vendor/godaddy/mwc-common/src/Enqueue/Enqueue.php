<?php

namespace GoDaddy\WordPress\MWC\Common\Enqueue;

use Closure;
use GoDaddy\WordPress\MWC\Common\Enqueue\Types\EnqueueScript;
use GoDaddy\WordPress\MWC\Common\Enqueue\Types\EnqueueStyle;

/**
 * Static asset enqueue handler.
 *
 * @since 1.0.0
 */
class Enqueue
{
    /** @var string type of asset being enqueued */
    protected $enqueueType;

    /** @var string enqueued asset handle */
    protected $handle;

    /** @var string the location of the asset to be enqueued (e.g. URL or path) */
    protected $source = '';

    /** @var string[] optional enqueued item's dependencies (array of handles) */
    protected $dependencies = [];

    /** @var string|null version tag for enqueued asset */
    protected $version;

    /** @var bool whether item loading should be deferred (default false) */
    protected $deferred = false;

    /** @var callable enqueueing condition */
    protected $enqueueCondition;

    /**
     * Sets the enqueue type.
     *
     * @since 1.0.0
     *
     * @param string $type the enqueue type
     * @return self
     */
    protected function setType(string $type) : self
    {
        $this->enqueueType = $type;

        return $this;
    }

    /**
     * Gets the enqueue type.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->enqueueType ?: '';
    }

    /**
     * Creates a instance for enqueuing scripts.
     *
     * @since 1.0.0
     *
     * @return EnqueueScript
     */
    public static function script() : EnqueueScript
    {
        return new EnqueueScript();
    }

    /**
     * Creates a instance for enqueuing stylesheets.
     *
     * @since 1.0.0
     *
     * @return EnqueueStyle
     */
    public static function style() : EnqueueStyle
    {
        return new EnqueueStyle();
    }

    /**
     * Sets the enqueued asset handle.
     *
     * @since 1.0.0
     *
     * @param string $handle the asset handle
     * @return self
     */
    public function setHandle(string $handle) : self
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * Sets the location of the asset to enqueue.
     *
     * @since 1.0.0
     *
     * @param string $source the asset's source location (e.g. URL or path)
     * @return self
     */
    public function setSource(string $source) : self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Sets dependencies for the asset being enqueued.
     *
     * @since 1.0.0
     *
     * @param string[] $dependencies array of asset identifiers (default none)
     * @return self
     */
    public function setDependencies(array $dependencies = []): self
    {
        $this->dependencies = $dependencies;

        return $this;
    }

    /**
     * Sets the file version.
     *
     * @since 1.0.0
     *
     * @param string $version
     * @return self
     */
    public function setVersion(string $version) : self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Sets whether enqueue should be deferred.
     *
     * @since 1.0.0
     *
     * @param bool $defer whether to defer script loading
     * @return self
     */
    public function setDeferred(bool $defer) : self
    {
        $this->deferred = $defer;

        return $this;
    }

    /**
     * Sets a condition in order to enqueue the asset.
     *
     * @since 1.0.0
     *
     * @param callable $condition closure or callable that returns bool
     * @return self
     */
    public function setCondition(callable $condition) : self
    {
        $this->enqueueCondition = $condition;

        return $this;
    }

    /**
     * Removes a condition for the enqueue to apply (will always apply).
     *
     * @since 1.0.0
     *
     * @return self
     */
    public function removeCondition() : self
    {
        $this->enqueueCondition = null;

        return $this;
    }

    /**
     * Determines whether a condition for enqueueing has been set.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function hasCondition() : bool
    {
        return null !== $this->enqueueCondition && ($this->enqueueCondition instanceof Closure || is_callable($this->enqueueCondition));
    }

    /**
     * Determines whether the asset should be enqueued based on the defined condition, if present.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function shouldEnqueue() : bool
    {
        return ! $this->hasCondition() || call_user_func($this->enqueueCondition);
    }
}
