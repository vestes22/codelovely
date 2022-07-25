<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin;

use Exception;
use GoDaddy\WordPress\MWC\Common\Enqueue\Enqueue;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin\Views\GoDaddyPaymentsPromotionBlock;
use GoDaddy\WordPress\MWC\Dashboard\Users\Permissions\ShowExtensionsRecommendationsPermission;
use stdClass;

class BrowseExtensionsPromotionBlocksOverride
{
    /** @var string the title of the GoDaddy Payments featured section */
    private $goDaddyPaymentsSectionTitle = 'GoDaddy Payments';

    /** @var string the title of the WC payments featured section */
    private $wcPaymentsSectionTitle = 'WooCommerce Payments';

    /**
     * BrowseExtensionsPromotionBlocksOverride constructor.
     *
     * @since 2.13.0
     */
    public function __construct()
    {
        $this->registerHooks();
    }

    /**
     * Registers hooks.
     *
     * @since 2.13.0
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('admin_init')
            ->setHandler([$this, 'registerAdminHooks'])
            ->execute();
    }

    /*
     * Registers hooks for admin requests.
     *
     * @since 2.13.0
     */
    public function registerAdminHooks()
    {
        if (! $this->shouldOverridePromotionBlocks()) {
            return;
        }

        Register::action()
            ->setGroup('woocommerce_page_wc-addons')
            ->setPriority(0)
            ->setHandler([$this, 'registerAddonsHooks'])
            ->execute();

        Register::action()
            ->setGroup('woocommerce_page_wc-addons')
            ->setPriority(100)
            ->setHandler([$this, 'renderPromotionBlock'])
            ->execute();

        Register::action()
            ->setGroup('load-woocommerce_page_wc-addons')
            ->setHandler([$this, 'enqueueAssets'])
            ->execute();
    }

    /**
     * Returns true if the promotions block should be overridden.
     *
     * @since 2.13.0
     */
    protected function shouldOverridePromotionBlocks() : bool
    {
        return $this->shouldShowRecommendationsToUser();
    }

    /**
     * Returns true if the user has given us permissions to show recommendations.
     *
     * @since 2.13.0
     *
     * @return bool
     */
    protected function shouldShowRecommendationsToUser() : bool
    {
        if (! $user = User::getCurrent()) {
            return false;
        }

        return (new ShowExtensionsRecommendationsPermission($user->getId()))->isAllowed();
    }

    /**
     * Register hooks for the Woocommerce Extensions admin page.
     *
     * @since 2.13.0
     */
    public function registerAddonsHooks()
    {
        Register::filter()
            ->setGroup('transient_wc_addons_featured')
            ->setHandler([$this, 'filterFeaturedSections'])
            ->execute();

        Register::filter()
            ->setGroup('pre_set_transient_wc_addons_featured')
            ->setHandler([$this, 'filterFeaturedSections'])
            ->execute();
    }

    /**
     * Inserts the GoDaddy Payments Promotion block in the sections array.
     *
     * @since 2.13.0
     *
     * @param object|bool $value
     * @return object|void
     */
    public function filterFeaturedSections($value)
    {
        if (! $value instanceof stdClass) {
            return $value;
        }

        $found = false;
        $definition = $this->getPromotionBlockClass('godaddy_payments_featured_extensions_promotion_block_button')->getDefinition();

        foreach ($value->sections as $index=>$featuredSection) {
            if (! property_exists($featuredSection, 'title')) {
                continue;
            }

            // strpos returns an integer, which can be 0, if found and false (bool) if not found.
            // Since 0 evaluates to false, have to check that the result is explicitly not false (bool).
            if ((strpos($featuredSection->title, $this->wcPaymentsSectionTitle) !== false) &&
                $featuredSection->module == 'promotion_block') {
                $value->sections[$index] = $definition;
                $found = true;
                break;
            }

            // also break if we already added the section, for example when the transient was being saved
            if ((strpos($featuredSection->title, $this->goDaddyPaymentsSectionTitle) !== false) &&
                $featuredSection->module == 'promotion_block') {
                $found = true;
                break;
            }
        }

        if (! $found) {
            array_unshift($value->sections, $definition);
        }

        return $value;
    }

    /**
     * Renders the promotion block for GoDaddy Payments.
     *
     * @since 2.13.0
     */
    public function renderPromotionBlock()
    {
        echo $this->getPromotionBlockClass($this->getActiveTab())->render();
    }

    /**
     * Returns an instance of GoDaddyPaymentsPromotionBlock.
     *
     * @since 2.13.0
     *
     * @param string $activeTab
     * @return GoDaddyPaymentsPromotionBlock
     */
    protected function getPromotionBlockClass(string $activeTab)
    {
        return new GoDaddyPaymentsPromotionBlock($activeTab);
    }

    /**
     * Enqueues/loads registered assets.
     *
     * @since 2.13.0
     *
     * @throws Exception
     */
    public function enqueueAssets()
    {
        Enqueue::script()
            ->setHandle('godaddy-payments-promotion')
            ->setSource(WordPressRepository::getAssetsUrl('js/payments/godaddy-payments/admin/promotion.js'))
            ->setDeferred(true)
            ->execute();

        Enqueue::style()
            ->setHandle('godaddy-payments-promotion')
            ->setSource(WordPressRepository::getAssetsUrl('css/promotion.css'))
            ->execute();
    }

    /**
     * Gets the active tab based on the section URL param.
     *
     * @since 2.13.0
     *
     * @return string
     */
    protected function getActiveTab()
    {
        switch (ArrayHelper::get($_REQUEST, 'section')) {
            case 'payment-gateways':
                return 'godaddy_payments_payment_extensions_promotion_block_button';
                break;
            default:
                return 'godaddy_payments_featured_extensions_promotion_block_button';
        }
    }
}
