<?php

namespace GoDaddy\WordPress\MWC\Common\Content;

use Exception;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Abstract WordPress Admin page class.
 *
 * Represents a base page for all WordPress admin pages to extend from.
 *
 * @since 1.0.0
 */
abstract class AbstractAdminPage extends AbstractPage
{
    /** @var string the minimum capability to have access to the related menu item */
    protected $capability;

    /** @var string the related menu title */
    protected $menuTitle;

    /** @var string the parent menu slug identifier */
    protected $parentMenuSlug;

    /** @var int|null position of page within the menu */
    protected $menuPosition = null;

    /**
     * WordPress admin page constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        parent::__construct();

        $this->registerMenuItem();
    }

    /**
     * Adds the menu page.
     *
     * @since 1.0.0
     *
     * @internal
     *
     * @see https://developer.wordpress.org/reference/functions/add_submenu_page/
     *
     * @return self
     */
    public function addMenuItem() : self
    {
        if (empty($this->getParentMenuSlug())) {
            // TODO: log an error using a wrapper for WC_Logger {WV 2021-02-15}
            // throw new Exception('The page parent menu slug property should be defined.');
        }

        add_submenu_page(
            $this->getParentMenuSlug(),
            $this->getTitle(),
            $this->getMenuTitle() ?? $this->getTitle(),
            $this->getCapability(),
            $this->getScreenId(),
            [$this, 'render'],
            $this->getMenuPosition()
        );

        return $this;
    }

    /**
     * Registers the menu page.
     *
     * @since 1.0.0
     *
     * @return self
     */
    protected function registerMenuItem() : self
    {
        try {
            if ($this->shouldAddMenuItem()) {
                Register::action()
                    ->setGroup('admin_menu')
                    ->setHandler([$this, 'addMenuItem'])
                    ->execute();
            }
        } catch (Exception $ex) {
            // TODO: log an error using a wrapper for WC_Logger {WV 2021-02-15}
            // throw new Exception('Cannot register the menu item: '.$ex->getMessage());
        }

        return $this;
    }

    /**
     * Checks if the menu item for this page should be added/registered or not.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function shouldAddMenuItem() : bool
    {
        return true;
    }

    /**
     * Registers the page assets.
     *
     * @since 1.0.0
     *
     * @return self
     */
    protected function registerAssets() : self
    {
        try {
            Register::action()
                ->setGroup('admin_enqueue_scripts')
                ->setHandler([$this, 'maybeEnqueueAssets'])
                ->execute();
        } catch (Exception $ex) {
            // TODO: log an error using a wrapper for WC_Logger {WV 2021-02-15}
            // throw new Exception('Cannot register assets: '.$ex->getMessage());
        }

        return $this;
    }

    /**
     * Sets the minimum capability to have access to this page.
     *
     * @since 1.0.0
     *
     * @param string $capability
     * @return AbstractAdminPage $this
     */
    public function setCapability(string $capability) : AbstractAdminPage
    {
        $this->capability = $capability;

        return $this;
    }

    /**
     * Gets the minimum capability to have access to this page.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getCapability() : string
    {
        return $this->capability;
    }

    /**
     * Sets the menu title for the page.
     *
     * @since 1.0.0
     *
     * @param string $menuTitle
     * @return AbstractAdminPage $this
     */
    public function setMenuTitle(string $menuTitle) : AbstractAdminPage
    {
        $this->menuTitle = $menuTitle;

        return $this;
    }

    /**
     * Gets the page menu title.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getMenuTitle() : string
    {
        return $this->menuTitle;
    }

    /**
     * Sets the parent menu slug for the page.
     *
     * @since 1.0.0
     *
     * @param string $parentMenuSlug
     * @return AbstractAdminPage $this
     */
    public function setParentMenuSlug(string $parentMenuSlug) : AbstractAdminPage
    {
        $this->parentMenuSlug = $parentMenuSlug;

        return $this;
    }

    /**
     * Gets the parent menu slug.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getParentMenuSlug() : string
    {
        return $this->parentMenuSlug;
    }

    /**
     * Gets the menu position.
     *
     * @return int|null
     */
    public function getMenuPosition()
    {
        return $this->menuPosition;
    }

    /**
     * Sets the menu position.
     *
     * @param int $menuPosition
     * @return $this
     */
    public function setMenuPosition(int $menuPosition) : AbstractAdminPage
    {
        $this->menuPosition = $menuPosition;

        return $this;
    }
}
