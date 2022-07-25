<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders;

use GoDaddy\WordPress\MWC\Common\Models\CurrencyAmount;
use GoDaddy\WordPress\MWC\Common\Traits\FulfillableTrait;

/**
 * A representation of a line Item in an order.
 *
 * @since 3.4.1
 */
class LineItem extends AbstractOrderItem
{
    use FulfillableTrait;

    /** @var int|float the line item's quantity */
    protected $quantity;

    /** @var CurrencyAmount the line item's total tax amount */
    protected $taxAmount;

    /** @var \WC_Product|bool product */
    protected $product;

    /** @var int|null variationId */
    protected $variationId;

    /** @var CurrencyAmount the line item's subtotal amount (before discounts) */
    protected $subTotalAmount;

    /** @var CurrencyAmount the line item's subtotal tax amount (before discounts) */
    protected $subTotalTaxAmount;

    /**
     * Gets the line item amount.
     *
     * @since 3.4.1
     *
     * @return int|float
     */
    public function getQuantity() : float
    {
        return $this->quantity;
    }

    /**
     * Gets the line item tax total amount.
     *
     * @since 3.4.1
     *
     * @return CurrencyAmount
     */
    public function getTaxAmount() : CurrencyAmount
    {
        return $this->taxAmount;
    }

    /**
     * Gets the line item's subtotal amount (before discounts).
     *
     * @return CurrencyAmount
     */
    public function getSubTotalAmount() : CurrencyAmount
    {
        return $this->subTotalAmount;
    }

    /**
     * Gets the line item's subtotal tax amount (before discounts).
     *
     * @return CurrencyAmount
     */
    public function getSubTotalTaxAmount() : CurrencyAmount
    {
        return $this->subTotalTaxAmount;
    }

    /**
     * Gets the line item product.
     *
     * @since 3.4.1
     *
     * @return \WC_Product|bool
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Gets the line item variationId for variable products.
     *
     * @since 3.4.1
     *
     * @return int|null
     */
    public function getVariationId()
    {
        return $this->variationId;
    }

    /**
     * Sets the line item quantity.
     *
     * @since 3.4.1
     *
     * @param int|float $quantity
     * @return LineItem
     */
    public function setQuantity(float $quantity) : LineItem
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Sets the line item tax total amount.
     *
     * @since 3.4.1
     *
     * @param CurrencyAmount $taxAmount
     * @return LineItem
     */
    public function setTaxAmount(CurrencyAmount $taxAmount) : LineItem
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    /**
     * Sets the line item's subtotal amount (before discounts).
     *
     * @param CurrencyAmount $value
     * @return LineItem
     */
    public function setSubTotalAmount(CurrencyAmount $value) : LineItem
    {
        $this->subTotalAmount = $value;

        return $this;
    }

    /**
     * Sets the line item's subtotal tax amount (before discounts).
     *
     * @param CurrencyAmount $value
     * @return LineItem
     */
    public function setSubTotalTaxAmount(CurrencyAmount $value) : LineItem
    {
        $this->subTotalTaxAmount = $value;

        return $this;
    }

    /**
     * Sets the line item product.
     *
     * @since 3.4.1
     *
     * @param \WC_Product|bool $product
     * @return LineItem
     */
    public function setProduct($product) : LineItem
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Sets the line item variationId for variable products.
     *
     * @since 3.4.1
     *
     * @param int $variationId
     * @return LineItem
     */
    public function setVariationId(int $variationId = null) : LineItem
    {
        $this->variationId = $variationId;

        return $this;
    }
}
