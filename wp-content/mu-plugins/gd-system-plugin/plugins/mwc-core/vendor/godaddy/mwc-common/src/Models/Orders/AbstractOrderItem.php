<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Models\AbstractModel;
use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * An abstract representation to Item in an Order.
 *
 * @since 3.4.1
 */
abstract class AbstractOrderItem extends AbstractModel
{
    use HasLabelTrait;

    /**
     * Order item ID.
     *
     * @var int
     */
    protected $id;

    /**
     * Order item total amount.
     *
     * @var CurrencyAmount
     */
    protected $totalAmount;

    /**
     * Gets order item ID.
     *
     * @since 3.4.1
     *
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * Gets order item total amount object.
     *
     * @since 3.4.1
     *
     * @return CurrencyAmount
     */
    public function getTotalAmount() : CurrencyAmount
    {
        return $this->totalAmount;
    }

    /**
     * Sets order item ID.
     *
     * @since 3.4.1
     *
     * @param int $id
     *
     * @return AbstractOrderItem
     */
    public function setId(int $id) : AbstractOrderItem
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets order item total amount object.
     *
     * @param CurrencyAmount $totalAmount
     *
     * @return AbstractOrderItem
     */
    public function setTotalAmount(CurrencyAmount $totalAmount) : AbstractOrderItem
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }
}
