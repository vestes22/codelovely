<?php

namespace GoDaddy\WordPress\MWC\Common\Models;

use DateTime;
use GoDaddy\WordPress\MWC\Common\Events\Events;

/**
 * Native review object.
 */
class Review extends AbstractModel
{
    /** @var User */
    protected $author;

    /** @var DateTime */
    protected $dateGmt;

    /** @var string */
    protected $content;

    /** @var int */
    protected $productId;

    /** @var string */
    protected $status;

    /**
     * Gets the review author.
     *
     * @return User|null
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Gets the review date GMT.
     *
     * @return DateTime|null
     */
    public function getDateGmt()
    {
        return $this->dateGmt;
    }

    /**
     * Gets the review content.
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Gets the product ID the review is associated with.
     *
     * @return int|null
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Gets the review status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the review author.
     *
     * @param User $value
     * @return self
     */
    public function setAuthor(User $value) : Review
    {
        $this->author = $value;

        return $this;
    }

    /**
     * Sets the review date GMT.
     *
     * @param DateTime|null $value
     * @return self
     */
    public function setDateGmt($value) : Review
    {
        $this->dateGmt = $value;

        return $this;
    }

    /**
     * Sets the review content.
     *
     * @param string $value
     * @return self
     */
    public function setContent(string $value) : Review
    {
        $this->content = $value;

        return $this;
    }

    /**
     * Sets the product ID the review is associated with.
     *
     * @param int|null $value
     * @return self
     */
    public function setProductId($value) : Review
    {
        $this->productId = $value;

        return $this;
    }

    /**
     * Sets the review status.
     *
     * @param string|null $value
     * @return self
     */
    public function setStatus($value) : Review
    {
        $this->status = $value;

        return $this;
    }

    /**
     * Saves the new review.
     *
     * This method also broadcast model events.
     *
     * @return self
     */
    public function save() : Review
    {
        parent::save();

        Events::broadcast($this->buildEvent('review', 'create'));

        return $this;
    }
}
