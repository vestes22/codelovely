<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use WC_Order;

/**
 * Payment gateways virtual terminal handler.
 */
class VirtualTerminal
{
    /** @var string */
    const ID = 'mwc-payments-virtual-terminal';

    /** @var string */
    const CTA_META_BOX_ID = 'mwc-virtual-terminal-notice';

    /** @var string */
    const NOTICE_BOX_ID = 'mwc-virtual-terminal-recommendation';

    /**
     * Virtual terminal constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /**
     * Adds action and filter hooks.
     *
     * @throws Exception
     */
    protected function addHooks()
    {
        Register::action()
                ->setGroup('woocommerce_order_item_add_action_buttons')
                ->setHandler([$this, 'renderActionButtonsHtml'])
                ->setArgumentsCount(1)
                ->execute();

        Register::action()
                ->setGroup('add_meta_boxes')
                ->setHandler([$this, 'addCallToActionMetaBox'])
                ->setPriority(40)
                ->execute();

        Register::action()
                ->setGroup('all_admin_notices')
                ->setHandler([$this, 'renderPendingNotice'])
                ->execute();

        Register::action()
                ->setGroup('admin_enqueue_scripts')
                ->setHandler([$this, 'enqueueCollectScript'])
                ->execute();
    }

    /**
     * Renders the Virtual Terminal HTML.
     *
     * @internal
     * @see VirtualTerminal::addHooks()
     *
     * @param WC_Order $order
     * @throws Exception
     */
    public function renderActionButtonsHtml($order)
    {
        $paymentMethod = $order->get_payment_method();

        if ($paymentMethod && ! CorePaymentGateways::isManagedPaymentGateway($paymentMethod)) {
            return;
        } ?>
        <span
            id="<?php echo esc_attr(static::ID); ?>"
            data-order-id="<?php echo esc_attr($order->get_id()); ?>"
            data-provider-name="<?php echo esc_attr($paymentMethod); ?>">
        </span>
        <?php
    }

    /**
     * Adds a side meta box with an empty element for displaying a call-to-action notice.
     *
     * @internal
     * @see VirtualTerminal::addHooks()
     */
    public function addCallToActionMetaBox()
    {
        add_meta_box(
            static::CTA_META_BOX_ID.'-meta-box',
            __('Virtual Terminal', 'mwc-core'),
            __CLASS__.'::renderCallToActionMetaBox',
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * Renders the call-to-action meta box HTML content.
     *
     * @internal
     * @see VirtualTerminal::addCallToActionMetaBox()
     */
    public static function renderCallToActionMetaBox()
    {
        echo '<div id="'.esc_attr(self::CTA_META_BOX_ID).'"></div>';
    }

    /**
     * Renders notice HTML if on pending woocommerce orders page.
     *
     * @internal
     * @see VirtualTerminal::addHooks()
     */
    public function renderPendingNotice()
    {
        $screen = WordPressRepository::getCurrentScreen();

        if (! $screen ||
            'order_list' !== $screen->getPageId() ||
            'wc-pending' !== ArrayHelper::get($_GET, 'post_status')) {
            return;
        }
        echo '<div id="'.esc_attr(self::NOTICE_BOX_ID).'"></div>';
    }

    /**
     * Enqueues the CollectJS script.
     *
     * @internal
     * @see VirtualTerminal::addHooks()
     *
     * @throws Exception
     */
    public function enqueueCollectScript()
    {
        $screen = WordPressRepository::getCurrentScreen();

        if (! $screen || 'edit_order' !== $screen->getPageId()) {
            return;
        }

        $sdkUrl = ManagedWooCommerceRepository::isProductionEnvironment()
            ? Configuration::get('payments.poynt.api.productionSdkUrl')
            : Configuration::get('payments.poynt.api.stagingSdkUrl');

        Enqueue::script()
               ->setHandle('poynt-collect-sdk')
               ->setSource($sdkUrl)
               ->execute();
    }
}
