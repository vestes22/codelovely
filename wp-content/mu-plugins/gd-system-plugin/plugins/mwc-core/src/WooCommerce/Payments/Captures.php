<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\OrdersRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Adapters\OrderAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\DataStores\WooCommerce\OrderPaymentTransactionDataStore;
use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\CaptureTransaction;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses\ApprovedTransactionStatus;
use WC_Order;

/**
 * Captures handler.
 */
class Captures
{
    /** @var string action capture order. */
    const ACTION_CAPTURE_ORDER = 'mwc_payments_capture_order';

    /**
     * Captures constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->registerHooks();

        // TODO: limit loading and handling to Core gateways, if any {@cwiseman 2021-05-16}
    }

    /**
     * Handles capture order ajax requests.
     */
    public function ajaxCaptureOrder()
    {
        try {
            $nonce = StringHelper::sanitize((string) ArrayHelper::get($_POST, 'nonce'));

            if (! wp_verify_nonce($nonce, static::ACTION_CAPTURE_ORDER)) {
                throw new Exception('Invalid permission.');
            }

            $wooOrder = OrdersRepository::get((int) ArrayHelper::get($_POST, 'orderId'));

            if (! $wooOrder instanceof WC_Order) {
                throw new Exception('Order not found.');
            }

            $results = $this->captureOrder($wooOrder);

            if (! $results->getStatus() instanceof ApprovedTransactionStatus) {
                throw new Exception($results->getResultMessage());
            }

            wp_send_json_success();
        } catch (Exception $exception) {
            wp_send_json_error([
                'message' => 'Order could not be captured. '.$exception->getMessage(),
            ]);
        }
    }

    /**
     * Captures a WooCommerce order.
     *
     * @param WC_Order $order
     * @return CaptureTransaction
     * @throws Exception
     */
    protected function captureOrder(WC_Order $order) : CaptureTransaction
    {
        $coreOrder = $this->convertOrder($order);

        $gateway = CorePaymentGateways::getManagedPaymentGatewayInstance(
            OrderPaymentTransactionDataStore::readProviderName($coreOrder->getId())
        );

        if (! $gateway) {
            throw new Exception(__('No gateway found for order', 'mwc-core'));
        }

        $transaction = $gateway->processCapture($gateway->getTransactionForCapture($order));

        // if the original auth amount has been captured, complete payment
        if (
               $transaction->getStatus() instanceof ApprovedTransactionStatus
            && $transaction->getTotalAmount()
            && $transaction->getOrder()
            && $transaction->getTotalAmount()->getAmount() >= $transaction->getOrder()->getTotalAmount()->getAmount()
        ) {

            // prevent stock from being reduced when payment is completed as this is done when the charge was authorized
            add_filter('woocommerce_payment_complete_reduce_order_stock', '__return_false', 100);

            // complete the order
            $order->payment_complete();
        }

        return $transaction;
    }

    /**
     * Enqueues the scripts.
     *
     * @internal callback
     * @see Captures::registerHooks()
     *
     * @param string $hookSuffix
     * @throws Exception
     */
    public function enqueueScripts($hookSuffix)
    {
        if ('post.php' !== $hookSuffix || 'shop_order' !== get_post_type()) {
            return;
        }

        Enqueue::script()
            ->setHandle('mwc-payments-captures')
            ->setSource(WordPressRepository::getAssetsUrl('js/payments/captures.js'))
            ->setDependencies(['jquery'])
            ->execute();
    }

    /**
     * Handles bulk actions.
     *
     * @internal callback
     * @see Captures::registerHooks()
     *
     * @param string $redirectTo
     * @param string $action
     * @param int[] $ids
     * @return string
     * @throws Exception
     */
    public function handleBulkActions($redirectTo, $action, $ids)
    {
        if (static::ACTION_CAPTURE_ORDER === $action) {
            foreach ($ids as $id) {
                if ($order = OrdersRepository::get((int) $id)) {
                    $this->maybeCaptureOrder($order);
                }
            }
        }

        return $redirectTo;
    }

    /**
     * Registers the bulk capture orders action.
     *
     * @internal callback
     * @see Captures::registerHooks()
     *
     * @param array $actions
     * @return array
     */
    public function maybeAddBulkActions($actions)
    {
        if (ArrayHelper::accessible($actions)) {
            $actions[static::ACTION_CAPTURE_ORDER] = __('Capture Charge', 'mwc-core');
        }

        return $actions;
    }

    /**
     * May add a capture button to order.
     *
     * @internal callback
     * @see Captures::registerHooks()
     *
     * @param null|WC_Order $order
     */
    public function maybeAddCaptureButton($order)
    {
        if (! $order instanceof WC_Order || 'shop_order' !== get_post_type($order->get_id())) {
            return;
        }

        try {
            $coreOrder = $this->convertOrder($order);

            $gateway = CorePaymentGateways::getManagedPaymentGatewayInstance(
                OrderPaymentTransactionDataStore::readProviderName($coreOrder->getId())
            );

            if (! $gateway) {
                return;
            }

            $this->renderCaptureButton($coreOrder);
        } catch (Exception $exception) {
            // TODO: Sentry logging {@cwiseman 2021-05-16}
        }
    }

    /**
     * May capture paid orders.
     *
     * @internal callback
     * @see Captures::registerHooks()
     *
     * @param $orderId
     * @param $oldStatus
     * @param $newStatus
     * @throws Exception
     */
    public function maybeCapturePaidOrder($orderId, $oldStatus, $newStatus)
    {
        $paidStatuses = OrdersRepository::getPaidStatuses();

        if (ArrayHelper::contains($paidStatuses, $oldStatus) || ! ArrayHelper::contains($paidStatuses, $newStatus)) {
            return;
        }

        if ($order = OrdersRepository::get((int) $orderId)) {

            // only proceed if the feature is enabled
            if (! Configuration::get('payments.'.$order->get_payment_method().'.capturePaidOrders', false)) {
                return;
            }

            // we will check if the gateway for capture is available in the following method
            $this->maybeCaptureOrder($order);
        }
    }

    /**
     * May capture an order.
     *
     * @param WC_Order $order
     * @return bool
     */
    protected function maybeCaptureOrder(WC_Order $order) : bool
    {
        try {
            $coreOrder = $this->convertOrder($order);

            if (! CorePaymentGateways::getManagedPaymentGatewayInstance(
                OrderPaymentTransactionDataStore::readProviderName($coreOrder->getId())
            )) {
                return false;
            }

            if ($coreOrder->isCaptured() || ! $coreOrder->isReadyForCapture()) {
                return false;
            }

            $captureTransaction = $this->captureOrder($order);

            return $captureTransaction->getStatus() instanceof ApprovedTransactionStatus;
        } catch (Exception $exception) {
            // @TODO implement exception handling {@acastro1 2021-05-13}
            return false;
        }
    }

    /**
     * Renders capture payment button for order.
     *
     * @param Order $order
     * @throws Exception
     */
    protected function renderCaptureButton(Order $order)
    {
        if (! $order->isReadyForCapture()) {
            return;
        }

        $tooltip = '';
        $buttonClasses = ['button', 'mwc-payments-capture'];

        if ($order->isCaptured()) {
            $buttonClasses = ArrayHelper::combine($buttonClasses, ['tips', 'disabled']);
            $tooltip = __('This charge has been fully captured', 'mwc-core');
        } else {
            $buttonClasses[] = 'button-primary';
        } ?>
        <button
            type="button"
            class="<?php echo esc_attr(implode(' ', $buttonClasses)); ?> <?php echo $tooltip ? 'data-tip="'.esc_attr($tooltip).'"' : ''; ?>"
        >
            <?php esc_html_e('Capture Charge', 'mwc-core'); ?>
        </button>
        <?php

        wc_enqueue_js(sprintf('window.mwc_payments_captures_handler = new MWCPaymentsCaptureHandler(%s)', ArrayHelper::jsonEncode([
            'action' => static::ACTION_CAPTURE_ORDER,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce(static::ACTION_CAPTURE_ORDER),
            'orderId' => $order->getId(),
            'i18n' => [
                'ays' => __('Are you sure you wish to process this capture? The action cannot be undone.', 'mwc-core'),
                'errorMessage' => __('Something went wrong, and the capture could not be completed. Please try again.', 'mwc-core'),
            ],
        ])));
    }

    /**
     * Converts a WooCommerce order to a native order object.
     *
     * @param WC_Order $order
     * @return Order
     * @throws Exception
     */
    protected function convertOrder(WC_Order $order) : Order
    {
        return (new OrderAdapter($order))->convertFromSource();
    }

    /**
     * Register captures actions.
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'enqueueScripts'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_order_status_changed')
            ->setArgumentsCount(3)
            ->setHandler([$this, 'maybeCapturePaidOrder'])
            ->execute();

        Register::filter()
            ->setGroup('handle_bulk_actions-edit-shop_order')
            ->setArgumentsCount(3)
            ->setHandler([$this, 'handleBulkActions'])
            ->execute();

        Register::filter()
            ->setGroup('bulk_actions-edit-shop_order')
            ->setHandler([$this, 'maybeAddBulkActions'])
            ->execute();

        Register::action()
            ->setGroup('wp_ajax_'.static::ACTION_CAPTURE_ORDER)
            ->setHandler([$this, 'ajaxCaptureOrder'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_order_item_add_action_buttons')
            ->setHandler([$this, 'maybeAddCaptureButton'])
            ->execute();
    }
}
