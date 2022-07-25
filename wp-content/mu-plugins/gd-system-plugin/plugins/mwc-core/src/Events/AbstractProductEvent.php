<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;
use GoDaddy\WordPress\MWC\CostOfGoods\WC_COG_Product;
use WC_Product;

/**
 * Abstract product event class.
 *
 * @since 2.10.0
 */
abstract class AbstractProductEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var WC_Product The product object */
    protected $product;

    /**
     * AbstractProductEvent constructor.
     */
    public function __construct()
    {
        $this->resource = 'product';
    }

    /**
     * Sets the WooCommerce product object for this event.
     *
     * @param WC_Product $product
     * @return self
     */
    public function setWooCommerceProduct(WC_Product $product) : self
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Gets the data for the event.
     *
     * @return array
     *
     * @throws Exception
     */
    public function getData() : array
    {
        return $this->product ? [
            'product' => ArrayHelper::combine([
                'id'       => $this->product->get_id(),
                'type'     => $this->product->get_type(),
                'status'   => $this->product->get_status(),
                'currency' => WooCommerceRepository::getCurrency(),
            ], $this->getCostData()),
        ] : [];
    }

    /**
     * May get the product cost if Cost of Goods is enabled.
     *
     * @since x.y.z
     *
     * @return array
     */
    protected function getCostData() : array
    {
        if (! $this->product || ! class_exists('\GoDaddy\WordPress\MWC\CostOfGoods\WC_COG_Product')) {
            return ['product_cost' => null];
        }

        return $this->product->is_type('variable') ? $this->getCostDataVariableProduct() : $this->getCostDataSimpleProduct();
    }

    /**
     * Gets the product cost for a simple product.
     *
     * @since x.y.z
     *
     * @return array
     */
    protected function getCostDataSimpleProduct() : array
    {
        $cost = WC_COG_Product::get_cost($this->product);

        return [
            'product_cost' => '' !== $cost ? (float) $cost : null,
        ];
    }

    /**
     * Gets the product costs for a variable product.
     *
     * @since x.y.z
     *
     * @return array
     */
    protected function getCostDataVariableProduct() : array
    {
        list($min, $max) = WC_COG_Product::get_variable_product_min_max_costs($this->product);
        $average = WC_COG_Product::get_variable_product_average_costs($this->product);

        return [
            'product_cost'     => '' !== $average ? (float) $average : null,
            'product_cost_min' => '' !== $min ? (float) $min : null,
            'product_cost_max' => '' !== $max ? (float) $max : null,
        ];
    }
}
