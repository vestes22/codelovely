<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use GoDaddy\WordPress\MWC\Common\Events\ModelEvent;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;

/**
 * Abstraction to be implemented by the platform models.
 */
abstract class AbstractModel implements ModelContract
{
    use CanConvertToArrayTrait;

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public static function create()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public static function get($identifier)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function update()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function delete()
    {
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function save()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public static function seed()
    {
        return null;
    }

    /**
     * Builds a model event.
     *
     * @param string $resource
     * @param string $action
     * @return ModelEvent
     */
    protected function buildEvent(string $resource, string $action) : ModelEvent
    {
        return new ModelEvent($this, $resource, $action);
    }
}
