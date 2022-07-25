<?php

namespace GoDaddy\WordPress\MWC\Common\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Models\Contracts\ModelContract;

/**
 * Generic event to be reused by model classes.
 */
class ModelEvent implements EventBridgeEventContract
{
    /** @var ModelContract the model with data for the current event */
    protected $model;

    /** @var string the name of the resource for the current event */
    protected $resource;

    /** @var string the name of the action for the current event */
    protected $action;

    /**
     * ModelEvent constructor.
     */
    public function __construct(ModelContract $model, string $resource, string $action)
    {
        $this->model = $model;
        $this->resource = $resource;
        $this->action = $action;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): array
    {
        return [
            'resource' => $this->model->toArray(),
        ];
    }
}
