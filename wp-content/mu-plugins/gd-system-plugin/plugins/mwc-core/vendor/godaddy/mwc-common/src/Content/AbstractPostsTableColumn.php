<?php

namespace GoDaddy\WordPress\MWC\Common\Content;

use GoDaddy\WordPress\MWC\Common\Content\Contracts\RenderableContract;
use GoDaddy\WordPress\MWC\Common\Register\Register;

abstract class AbstractPostsTableColumn implements RenderableContract
{
    /** @var string post type associated with this column */
    protected $postType = '';

    /** @var string the slug for the column */
    protected $slug;

    /** @var string the name for the column */
    protected $name;

    /** @var int the priority for the filter that registers the column */
    protected $registerPriority = 10;

    /**
     * AbstractPostsTableColumn constructor.
     *
     * @since 3.4.1
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Gets the post type.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getPostType(): string
    {
        return $this->postType;
    }

    /**
     * Sets the post type.
     *
     * @since 3.4.1
     *
     * @param string $postType
     * @return AbstractPostsTableColumn $this
     */
    public function setPostType(string $postType) : AbstractPostsTableColumn
    {
        $this->postType = $postType;

        return $this;
    }

    /**
     * Gets the slug.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Sets the slug.
     *
     * @since 3.4.1
     *
     * @param string $slug
     * @return AbstractPostsTableColumn $this
     */
    public function setSlug(string $slug) : AbstractPostsTableColumn
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Gets the name.
     *
     * @since 3.4.1
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name.
     *
     * @since 3.4.1
     *
     * @param string $name
     * @return AbstractPostsTableColumn $this
     */
    public function setName(string $name) : AbstractPostsTableColumn
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Gets the register priority.
     *
     * @return int
     */
    public function getRegisterPriority(): int
    {
        return $this->registerPriority;
    }

    /**
     * Sets the register priority.
     *
     * @since 3.4.1
     *
     * @param int $registerPriority
     * @return AbstractPostsTableColumn $this
     */
    public function setRegisterPriority(int $registerPriority) : AbstractPostsTableColumn
    {
        $this->registerPriority = $registerPriority;

        return $this;
    }

    /**
     * Registers the table column hooks.
     *
     * @since 3.4.1
     */
    protected function addHooks()
    {
        if (! $this->getPostType()) {
            return;
        }

        Register::filter()
            ->setGroup("manage_{$this->getPostType()}_posts_columns")
            ->setHandler([$this, 'register'])
            ->setPriority($this->getRegisterPriority())
            ->execute();

        Register::action()
            ->setGroup("manage_{$this->getPostType()}_posts_custom_column")
            ->setHandler([$this, 'maybeRender'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Adds an entry to the columns array and returns the array.
     *
     * @since 3.4.1
     *
     * @param array $columns
     * @return array $columns
     */
    public function register(array $columns) : array
    {
        $columns[$this->getSlug()] = $this->getName();

        return $columns;
    }

    /**
     * Calls render() if shouldRender() returns true.
     *
     * @since 3.4.1
     *
     * @param string $slug
     * @param int $postId
     */
    public function maybeRender(string $slug, int $postId)
    {
        if ($this->shouldRender($slug, $postId)) {
            $this->render($postId);
        }
    }

    /**
     * Returns true if the given slug matches the column slug.
     *
     * @since 3.4.1
     *
     * @param string $slug
     * @param int $postId
     */
    protected function shouldRender(string $slug, int $postId)
    {
        return $slug === $this->getSlug();
    }

    /**
     * @param int|null $postId
     * @return mixed
     */
    abstract public function render(int $postId = null);
}
