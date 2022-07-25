<?php

namespace GoDaddy\WordPress\MWC\Common\Register;

use Closure;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterAction;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterFilter;

/**
 * Registers an item.
 */
class Register
{
    /** @var string registration type */
    protected $registrableType;

    /** @var string group name containing other items the item should be registered with */
    protected $groupName;

    /** @var array|string|Closure function, method or closure to be attached to the registered item's execution */
    protected $handler;

    /** @var int number of arguments to pass the handler */
    protected $numberOfArguments;

    /** @var int priority of the item being registered */
    protected $processPriority;

    /** @var callable condition for successful registration */
    protected $registrableCondition;

    /**
     * Registers an action.
     *
     * @return RegisterAction
     */
    public static function action() : RegisterAction
    {
        return new RegisterAction();
    }

    /**
     * Registers a filter.
     *
     * @return RegisterFilter
     */
    public static function filter() : RegisterFilter
    {
        return new RegisterFilter();
    }

    /**
     * Sets the registrable type for the current object.
     *
     * @param string $type a registrable type
     * @return $this
     */
    protected function setType(string $type) : self
    {
        $this->registrableType = $type;

        return $this;
    }

    /**
     * Gets the registrable type.
     *
     * @return string
     */
    public function getType() : string
    {
        return $this->registrableType ?: '';
    }

    /**
     * Sets the group name to register the handler to.
     *
     * @param string $name name of the group to register the handler to
     * @return $this
     */
    public function setGroup(string $name) : self
    {
        $this->groupName = $name;

        return $this;
    }

    /**
     * Sets a handler for the item to register.
     *
     * @param string|array|Closure $handler function name (string), static method name (string) or array (object name, method name)
     * @return $this
     */
    public function setHandler($handler) : self
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * Determines if the item to register has a handler attached.
     *
     * @return bool
     */
    protected function hasHandler() : bool
    {
        return null !== $this->handler && ($this->handler instanceof Closure || is_callable($this->handler));
    }

    /**
     * Sets the priority for where in the overall order the registration should be processed.
     *
     * @param int|null $priority
     * @return $this
     */
    public function setPriority(int $priority = null) : self
    {
        $this->processPriority = $priority;

        return $this;
    }

    /**
     * Sets if the arguments to pass to the handler upon registration.
     *
     * @param int $arguments
     * @return $this
     */
    public function setArgumentsCount(int $arguments) : self
    {
        $this->numberOfArguments = $arguments;

        return $this;
    }

    /**
     * Sets a condition according to which the registration should apply.
     *
     * @param callable $registrableCondition
     * @return $this
     */
    public function setCondition(callable $registrableCondition) : self
    {
        $this->registrableCondition = $registrableCondition;

        return $this;
    }

    /**
     * Removes a condition for registration to apply (will always apply).
     *
     * @return $this
     */
    public function removeCondition() : self
    {
        $this->registrableCondition = null;

        return $this;
    }

    /**
     * Determines whether there is a condition to apply the registration.
     *
     * @return bool
     */
    protected function hasCondition() : bool
    {
        return null !== $this->registrableCondition;
    }

    /**
     * Determines whether the registration should apply based on the defined condition, if present.
     *
     * @return bool
     */
    protected function shouldRegister() : bool
    {
        return ! $this->hasCondition() || call_user_func($this->registrableCondition);
    }
}
