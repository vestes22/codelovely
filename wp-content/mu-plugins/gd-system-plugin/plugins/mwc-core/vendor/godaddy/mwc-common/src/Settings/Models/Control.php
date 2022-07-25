<?php

namespace GoDaddy\WordPress\MWC\Common\Settings\Models;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ControlContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Settings control model.
 */
class Control implements ControlContract
{
    use CanConvertToArrayTrait;
    use HasLabelTrait;

    /** @var string checkbox type */
    const TYPE_CHECKBOX = 'checkbox';

    /** @var string color picker type */
    const TYPE_COLOR_PICKER = 'colorPicker';

    /** @var string date type */
    const TYPE_DATE = 'date';

    /** @var string email type */
    const TYPE_EMAIL = 'email';

    /** @var string file type */
    const TYPE_FILE = 'file';

    /** @var string number type */
    const TYPE_NUMBER = 'number';

    /** @var string password type */
    const TYPE_PASSWORD = 'password';

    /** @var string radio type */
    const TYPE_RADIO = 'radio';

    /** @var string range type */
    const TYPE_RANGE = 'range';

    /** @var string select type */
    const TYPE_SELECT = 'select';

    /** @var string text type */
    const TYPE_TEXT = 'text';

    /** @var string textarea type */
    const TYPE_TEXTAREA = 'textarea';

    /** @var string image upload type */
    const TYPE_IMAGE_UPLOAD = 'imageUpload';

    /** @var string|null identifier */
    protected $id;

    /** @var string|null control input type */
    protected $type;

    /** @var string description */
    protected $description = '';

    /** @var array options */
    protected $options = [];

    /** @var mixed|null placeholder (optional) */
    protected $placeholder;

    /**
     * Gets the setting control ID.
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the setting control type.
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the setting control description.
     *
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }

    /**
     * Gets the setting control options.
     *
     * @return array
     */
    public function getOptions() : array
    {
        return $this->options;
    }

    /**
     * Gets the control placeholder.
     *
     * @return mixed|null
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * Sets the setting control ID.
     *
     * @param string $value
     * @return self
     */
    public function setId(string $value)
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the setting control type.
     *
     * @param string $value
     * @return self
     */
    public function setType(string $value)
    {
        $allowedTypes = $this->getAllowedTypes();

        if (! ArrayHelper::contains($allowedTypes, $value)) {
            throw new InvalidArgumentException(sprintf(
                __('Invalid value for updating the control type of %1s: must be one of %2$s.', 'mwc-core'),
                $this->getLabel() ?? get_class($this),
                implode(', ', $allowedTypes)
            ));
        }

        $this->type = $value;

        return $this;
    }

    /**
     * Gets a list of allowed types.
     *
     * @return string[]
     */
    protected function getAllowedTypes() : array
    {
        $allowedTypes = [];
        $constants = (new ReflectionClass($this))->getConstants();

        foreach ($constants as $key => $value) {
            if (StringHelper::startsWith($key, 'TYPE_')) {
                $allowedTypes[] = $value;
            }
        }

        return $allowedTypes;
    }

    /**
     * Sets the setting control description.
     *
     * @param string $value
     * @return self
     */
    public function setDescription(string $value)
    {
        $this->description = $value;

        return $this;
    }

    /**
     * Sets the setting control options.
     *
     * @param array $value
     * @return self
     */
    public function setOptions(array $value)
    {
        $this->options = $value;

        return $this;
    }

    /**
     * Sets the control placeholder.
     *
     * @param mixed $value
     * @return self
     */
    public function setPlaceholder($value) : Control
    {
        $this->placeholder = $value;

        return $this;
    }
}
