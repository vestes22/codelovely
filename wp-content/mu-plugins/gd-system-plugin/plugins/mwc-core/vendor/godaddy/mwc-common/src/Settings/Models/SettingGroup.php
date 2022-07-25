<?php

namespace GoDaddy\WordPress\MWC\Common\Settings\Models;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Traits\HasSettingsTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * An object model for representing a setting group.
 */
class SettingGroup extends AbstractModel implements ConfigurableContract
{
    use HasLabelTrait;
    use HasSettingsTrait;

    /** @var string identifier */
    protected $id;

    /**
     * Gets the setting group ID.
     *
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Sets the group ID.
     *
     * @param string $value
     * @return SettingGroup
     */
    public function setId(string $value) : SettingGroup
    {
        $this->id = $value;

        return $this;
    }
}
