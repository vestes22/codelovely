<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Integrations;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\AbstractPaymentGateway;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Integrations\Contracts\IntegrationContract;
use WC_Order;

/**
 * A base class for integrations that handle tokenized transactions.
 */
abstract class AbstractTokenizedIntegration implements IntegrationContract
{
    /** @var string */
    protected $orderPaymentMetaKey;

    /** @var AbstractPaymentGateway */
    protected $gateway;

    /**
     * AbstractTokenizedIntegration constructor.
     *
     * @param AbstractPaymentGateway $gateway
     *
     * @throws Exception
     */
    public function __construct(AbstractPaymentGateway $gateway)
    {
        if (! $this->isAvailable()) {
            return;
        }

        $this->gateway = $gateway;

        $this->orderPaymentMetaKey = "_{$gateway->id}_payment";

        $this->registerHooks();
    }

    /**
     * Gets the gateway instance.
     *
     * @return AbstractPaymentGateway|null
     */
    protected function getGateway()
    {
        return $this->gateway;
    }

    /**
     * Gets the order object for the current Pay Page, if any.
     *
     * @return WC_Order|null
     */
    protected function getPayPageOrder()
    {
        global $wp;

        if ($wooOrder = OrdersRepository::get((int) ArrayHelper::get($wp->query_vars, 'order-pay', 0))) {
            return $wooOrder;
        } else {
            return null;
        }
    }

    /**
     * Determines whether the integration is available.
     *
     * @return bool
     */
    abstract protected function isAvailable() : bool;

    /**
     * Determines if currently paying for an order from this gateway.
     *
     * @param WC_Order $wooOrder
     * @return bool
     */
    protected function isGatewayPayPage(WC_Order $wooOrder) : bool
    {
        return WooCommerceRepository::isCheckoutPayPage() && $wooOrder->get_payment_method() === $this->getGateway()->id;
    }

    /**
     * Maybe force tokenization.
     *
     * @param mixed $isForced
     * @param mixed $gatewayId
     *
     * @return mixed
     */
    public function maybeForceTokenization($isForced, $gatewayId)
    {
        if ($isForced || $gatewayId !== $this->getGateway()->id) {
            return $isForced;
        }

        if ($wooOrder = $this->getPayPageOrder()) {
            return $this->isGatewayPayPage($wooOrder) && $this->shouldForceOrderTokenization($wooOrder);
        } else {
            return $this->shouldForceCartTokenization();
        }
    }

    /**
     * Registers the action & filter hooks.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::filter()
            ->setGroup('mwc_payments_force_tokenization')
            ->setHandler([$this, 'maybeForceTokenization'])
            ->setArgumentsCount(2)
            ->execute();
    }

    /**
     * Determines whether tokenization should be forced for the current cart.
     *
     * @return bool
     */
    abstract protected function shouldForceCartTokenization() : bool;

    /**
     * Determines whether tokenization should be forced for the given order.
     *
     * @param WC_Order $wooOrder
     *
     * @return bool
     */
    abstract protected function shouldForceOrderTokenization(WC_Order $wooOrder) : bool;
}
