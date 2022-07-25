<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Products;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;

/**
 * Native product object.
 */
class Product extends AbstractModel
{
    /** @var int|null */
    protected $id;

    /** @var string|null */
    protected $type;

    /** @var string|null */
    protected $status;

    /**
     * Gets the product ID.
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets the product type.
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the product status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the product ID.
     *
     * @param int $value
     * @return self
     */
    public function setId(int $value) : Product
    {
        $this->id = $value;

        return $this;
    }

    /**
     * Sets the product type.
     *
     * @param string $value
     * @return self
     */
    public function setType(string $value) : Product
    {
        $this->type = $value;

        return $this;
    }

    /**
     * Sets the product status.
     *
     * @param string $value
     * @return self
     */
    public function setStatus(string $value) : Product
    {
        $this->status = $value;

        return $this;
    }
}
