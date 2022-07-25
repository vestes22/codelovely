<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Views\Components;

use Exception;
use GoDaddy\WordPress\MWC\Common\Content\Contracts\RenderableContract;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Common\Traits\IsSingletonTrait;

class GoDaddyBranding implements RenderableContract
{
    use IsSingletonTrait;

    /** @var string[] Additional style tags to be rendered with this component, if needed */
    protected $styles = [];

    /**
     * Adds a style tag to be rendered with this component.
     *
     * @param string $style
     * @return self
     */
    public function addStyle(string $style) : self
    {
        $this->styles[] = $style;

        return $this;
    }

    /**
     * Gets the style tags to be rendered with this component.
     *
     * @return string[]
     */
    public function getStyles() : array
    {
        return array_values(array_unique($this->styles));
    }

    /**
     * Renders component markup.
     *
     * @throws Exception
     * @since 3.0.0
     */
    public function render()
    {
        foreach ($this->getStyles() as $style) {
            echo $style;
        } ?>
        <img id="mwc-gd-branding" class="mwc-gd-branding" src="<?php echo esc_url(WordPressRepository::getAssetsUrl('images/branding/provided-by-gd.svg')); ?>" alt="<?php esc_attr_e('Provided by GoDaddy', 'mwc-core'); ?>" />
        <?php
    }
}
