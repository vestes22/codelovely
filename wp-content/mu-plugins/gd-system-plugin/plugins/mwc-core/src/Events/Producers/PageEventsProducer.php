<?php

namespace GoDaddy\WordPress\MWC\Core\Events\Producers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\ProducerContract;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\DeprecationHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Events\PageViewEvent;
use GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\CostOfGoods;
use GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\Events\ProfitReportsPageViewEvent;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentSettingsPageViewEvent;
use WP_Screen;

class PageEventsProducer implements ProducerContract
{
    /**
     * Sets up the Coupon events producer.
     *
     * @deprecated
     *
     * @throws Exception
     */
    public function setup()
    {
        DeprecationHelper::deprecatedFunction(__METHOD__, '2.18.1', __CLASS__.'::load');

        $this->load();
    }

    /**
     * Loads the component.
     *
     * @throws Exception
     */
    public function load()
    {
        Register::action()
            ->setGroup('current_screen')
            ->setHandler([$this, 'firePageViewEvent'])
            ->execute();

        Register::action()
            ->setGroup('current_screen')
            ->setHandler([$this, 'maybeFirePaymentSettingsPageViewEvent'])
            ->execute();

        Register::action()
            ->setGroup('current_screen')
            ->setHandler([$this, 'maybeFireProfitReportsPageViewEvent'])
            ->execute();
    }

    /**
     * Fires page view event.
     *
     * @internal
     *
     * @param WP_Screen $currentWPScreen
     *
     * @throws Exception
     */
    public function firePageViewEvent($currentWPScreen)
    {
        if (! ArrayHelper::contains(['edit', 'post', 'woocommerce_page_wc-settings'], $currentWPScreen->base)) {
            return;
        }

        if ($currentScreen = WordPressRepository::getCurrentScreen()) {
            Events::broadcast(new PageViewEvent($currentScreen));
        }
    }

    /**
     * Fires Payment Settings page view event if on Payment Settings page.
     *
     * @internal
     *
     * @param WP_Screen $currentWPScreen
     *
     * @throws Exception
     */
    public function maybeFirePaymentSettingsPageViewEvent($currentWPScreen)
    {
        if ('woocommerce_page_wc-settings' !== $currentWPScreen->base) {
            return;
        }

        if (($currentScreen = WordPressRepository::getCurrentScreen()) && 'woocommerce_settings_checkout' === $currentScreen->getPageId()) {
            Events::broadcast(new PaymentSettingsPageViewEvent($currentScreen));
        }
    }

    /**
     * Fires a page view event when navigating to WooCommerce > Profit > Reports pages.
     *
     * @internal
     *
     * @param WP_Screen $currentWPScreen
     *
     * @throws Exception
     */
    public function maybeFireProfitReportsPageViewEvent($currentWPScreen)
    {
        if ('woocommerce_page_wc-reports' !== $currentWPScreen->base || ! CostOfGoods::shouldLoadConditionalFeature()) {
            return;
        }

        $currentScreen = WordPressRepository::getCurrentScreen();

        if ($currentScreen && 'profit' === ArrayHelper::get($_GET, 'tab')) {
            Events::broadcast(new ProfitReportsPageViewEvent($currentScreen, 'profit'));
        }
    }
}
