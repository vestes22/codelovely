<?php

namespace GoDaddy\WordPress\MWC\Common\Content;

use Exception;
use GoDaddy\WordPress\MWC\Common\Content\Contracts\RenderableContract;
use GoDaddy\WordPress\MWC\Common\Register\Register;

/**
 * Abstract metabox class.
 *
 * Represents a base metabox for all metaboxes to extend from.
 *
 * @since 3.4.1
 */
abstract class AbstractPostMetabox implements RenderableContract
{
    const CONTEXT_NORMAL = 'normal';
    const CONTEXT_SIDE = 'side';
    const CONTEXT_ADVANCED = 'advanced';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CORE = 'core';
    const PRIORITY_DEFAULT = 'default';
    const PRIORITY_LOW = 'low';

    /** @var string The post type associated with this metabox */
    protected $postType = ''; // default empty string ensures instantiating test mocks won't throw errors

    /** @var string The ID for the metabox */
    protected $id;

    /** @var string The visible title for the metabox */
    protected $title;

    /** @var string The context for the metabox. One of normal, side, or advanced. */
    protected $context = self::CONTEXT_NORMAL;

    /** @var string The priority for the metabox. One of high, core, default, or low. */
    protected $priority = self::PRIORITY_DEFAULT;

    /**
     * AbstractPostMetabox constructor.
     *
     * @since 3.4.1
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Registers the metabox hooks.
     *
     * @throws Exception
     * @since 3.4.1
     */
    protected function addHooks()
    {
        if (! $this->getPostType()) {
            return;
        }

        Register::action()
            ->setGroup("add_meta_boxes_{$this->getPostType()}")
            ->setHandler([$this, 'register'])
            ->execute();
    }

    /**
     * Sets the post type for the metabox.
     *
     * @since 3.4.1
     *
     * @param string $value
     * @return AbstractPostMetabox
     */
    public function setPostType(string $value) : AbstractPostMetabox
    {
        $this->postType = $value;

        return $this;
    }

    /**
     * Gets the post type for the metabox.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getPostType() : string
    {
        return $this->postType;
    }

    /**
     * Sets the ID for the metabox.
     *
     * @since 3.4.1
     *
     * @param string $value
     * @return AbstractPostMetabox
     */
    public function setId(string $value) : AbstractPostMetabox
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Gets the ID for the metabox.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getId() : string
    {
        return $this->id;
    }

    /**
     * Sets the title for the metabox.
     *
     * @since 3.4.1
     *
     * @param string $value
     * @return AbstractPostMetabox
     */
    public function setTitle(string $value) : AbstractPostMetabox
    {
        $this->title = $value;

        return $this;
    }

    /**
     * Gets the title for the metabox.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getTitle() : string
    {
        return $this->title;
    }

    /**
     * Sets the context for the metabox.
     *
     * @since 3.4.1
     *
     * @param string $value
     * @return AbstractPostMetabox
     */
    public function setContext(string $value) : AbstractPostMetabox
    {
        $this->context = $value;

        return $this;
    }

    /**
     * Gets the context for the metabox.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getContext() : string
    {
        return $this->context;
    }

    /**
     * Sets the priority for the metabox.
     *
     * @since 3.4.1
     *
     * @param string $value
     * @return AbstractPostMetabox
     */
    public function setPriority(string $value) : AbstractPostMetabox
    {
        $this->priority = $value;

        return $this;
    }

    /**
     * Gets the priority for the metabox.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getPriority() : string
    {
        return $this->priority;
    }

    /**
     * Registers the meta box.
     *
     * @since 3.4.1
     * @internal
     *
     * @param \WP_Post $post
     */
    public function register($post)
    {
        add_meta_box(
            $this->getId(),
            $this->getTitle(),
            [$this, 'render'],
            null,
            $this->getContext(),
            $this->getPriority()
        );
    }

    /**
     * Renders metabox markup.
     *
     * @since 3.4.1
     *
     * @param \WP_Post|null $post
     * @param array $args
     */
    abstract public function render($post = null, $args = []);
}
