<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\GoDaddyPayments\Frontend\Admin;

use GoDaddy\WordPress\MWC\Common\Models\User;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;
use GoDaddy\WordPress\MWC\Dashboard\Users\Permissions\ShowExtensionsRecommendationsPermission;

/**
 * Overrides the Set up payments task in WooCommerce > Home.
 */
class SetUpPaymentsTaskOverride
{
    /**
     * Constructor.
     *
     * @since 2.13.0
     */
    public function __construct()
    {
        $this->registerHooks();
    }

    /**
     * Register the hooks to override the Set up payments task.
     *
     * @since 2.13.0
     */
    protected function registerHooks()
    {
        Register::action()
            ->setGroup('admin_enqueue_scripts')
            ->setHandler([$this, 'maybeAddInlineScript'])
            ->execute();
    }

    /**
     * Tries to add an inline script to override the Set up payments task.
     *
     * @internal
     *
     * @since 2.13.0
     */
    public function maybeAddInlineScript()
    {
        if ($this->shouldAddInlineScript()) {
            $this->addInlineScript();
        }
    }

    /**
     * Determines whether we should add the inline script to override the Set up payments task.
     *
     * @since 2.13.0
     *
     * @return bool
     */
    protected function shouldAddInlineScript() : bool
    {
        return ! ManagedWooCommerceRepository::hasEcommercePlan() && WooCommerceRepository::isWooCommerceAdminPage() && $this->canShowRecommendationsToUser();
    }

    /**
     * Determines whether we can show recommendations to the current user.
     *
     * @since 2.13.0
     *
     * @return bool
     */
    protected function canShowRecommendationsToUser() : bool
    {
        if (! $user = User::getCurrent()) {
            return false;
        }

        return (new ShowExtensionsRecommendationsPermission($user->getId()))->isAllowed();
    }

    /**
     * Adds an inline script after the wp-hooks script.
     *
     * @since 2.13.0
     */
    protected function addInlineScript()
    {
        wp_add_inline_script('wp-hooks', $this->getInlineScript());
    }

    /**
     * Gets the JavaScript code to include inline after wp-hooks script.
     *
     * @since 2.13.0
     *
     * @return string
     */
    protected function getInlineScript() : string
    {
        $url = admin_url('admin.php?page=wc-settings&tab=checkout&gdpsetup=true&source=godaddy_payments_set_up_payments_task_button');

        return "
            wp.hooks.addFilter('woocommerce_admin_onboarding_task_list', 'godaddy/mwc/set-up-payments-task-override', function(tasks, query) {
                if (tasks.length) {
                    let payments = tasks.find(element => element.key === 'payments');

                    if (payments) {
                        payments.onClick = () => window.location = '".esc_url_raw($url)."';
                    }
                }

                return tasks;
            });
        ";
    }
}
