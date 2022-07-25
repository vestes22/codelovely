<?php

namespace GoDaddy\WordPress\MWC\Common\Content;

use GoDaddy\WordPress\MWC\Common\Content\Contracts\RenderableContract;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;

/**
 * Abstract page class.
 *
 * Represents a base page for all pages to extend from
 *
 * @since 1.0.0
 */
abstract class AbstractPage implements RenderableContract
{
    /** @var string page screen identifier */
    protected $screenId;

    /** @var string page title */
    protected $title;

    /**
     * Abstract page constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->registerAssets();
    }

    /**
     * Determines if the current page is the page we want to enqueue the registered assets.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function shouldEnqueueAssets() : bool
    {
        return WordPressRepository::isCurrentPage('toplevel_page_'.strtolower($this->screenId));
    }

    /**
     * Renders the page HTML markup.
     *
     * @since 1.0.0
     */
    public function render()
    {
        //@NOTE implement render() method.
    }

    /**
     * Maybe enqueues the page necessary assets.
     *
     * @since 1.0.0
     */
    public function maybeEnqueueAssets()
    {
        if (! $this->shouldEnqueueAssets()) {
            return;
        }

        $this->enqueueAssets();
    }

    /**
     * Enqueues/loads registered the page assets.
     *
     * @since 1.0.0
     */
    protected function enqueueAssets()
    {
        //@NOTE implement assets loading for the page.
    }

    /**
     * Registers any page assets.
     *
     * @since 1.0.0
     */
    protected function registerAssets()
    {
        //@NOTE implement assets registration for the page
    }

    /**
     * Sets the screen ID for the page.
     *
     * @since 1.0.0
     *
     * @param string $screenId
     * @return AbstractPage $this
     */
    public function setScreenId(string $screenId) : AbstractPage
    {
        $this->screenId = $screenId;

        return $this;
    }

    /**
     * Gets the screen ID for the page.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getScreenId() : string
    {
        return $this->screenId;
    }

    /**
     * Sets the title for the page.
     *
     * @since 1.0.0
     *
     * @param string $title
     * @return AbstractPage $this
     */
    public function setTitle(string $title) : AbstractPage
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets the page title.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getTitle() : string
    {
        return $this->title;
    }
}
