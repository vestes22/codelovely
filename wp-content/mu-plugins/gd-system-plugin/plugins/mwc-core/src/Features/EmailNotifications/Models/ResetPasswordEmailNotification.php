<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\WooCommerceEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsWooCommerceEmailNotificationTrait;
use WC_Email_Customer_Reset_Password;

/**
 * The reset password email notification model.
 */
class ResetPasswordEmailNotification extends EmailNotification implements WooCommerceEmailNotificationContract
{
    use IsWooCommerceEmailNotificationTrait;

    /** @var string */
    protected $id = 'customer_reset_password';

    /** @var string[] */
    protected $categories = ['customer'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this
            ->setName($this->getId())
            ->setLabel(__('Reset password', 'mwc-core'))
            ->setDescription(__('Sent to customers when they reset their passwords.', 'mwc-core'));
    }

    /**
     * Gets additional data for this email notification.
     *
     * @return array
     */
    protected function getAdditionalData() : array
    {
        return [
            'internal' => [
                'content' => $this->getMainContentHtml(),
            ],
        ];
    }

    /**
     * Gets preview data for the custom components that represent non-editable parts for previewing the email notification.
     *
     * @return array
     */
    protected function getAdditionalPreviewData() : array
    {
        if (! $wcEmail = $this->getWooCommerceCustomerResetPasswordEmail()) {
            return [];
        }

        // set example data for preview
        $wcEmail->user_login = 'example_user_name';
        $wcEmail->reset_key = 'example_reset_key';
        $wcEmail->user_id = 123;

        return $this->getAdditionalData();
    }

    /**
     * Gets the HTML for the main content section of the email.
     *
     * @return string
     */
    protected function getMainContentHtml() : string
    {
        if (! $wcEmail = $this->getWooCommerceCustomerResetPasswordEmail()) {
            return '';
        }

        ob_start();

        /* translators: %s: Customer username */ ?>
        <p><?php printf(esc_html__('Hi %s,', 'woocommerce'), esc_html($wcEmail->user_login)); ?></p>
        <?php /* translators: %s: Store name */ ?>
        <p><?php printf(esc_html__('Someone has requested a new password for the following account on %s:', 'woocommerce'), esc_html(wp_specialchars_decode($this->getSiteTitle(), ENT_QUOTES))); ?></p>
        <?php /* translators: %s: Customer username */ ?>
        <p><?php printf(esc_html__('Username: %s', 'woocommerce'), esc_html($wcEmail->user_login)); ?></p>
        <p><?php esc_html_e('If you didn\'t make this request, just ignore this email. If you\'d like to proceed:', 'woocommerce'); ?></p>
        <p>
        <a class="link" href="<?php echo esc_url(add_query_arg(['key' => $wcEmail->reset_key, 'id' => $wcEmail->user_id], wc_get_endpoint_url('lost-password', '', wc_get_page_permalink('myaccount')))); ?>">
            <?php esc_html_e('Click here to reset your password', 'woocommerce'); ?>
        </a>
        </p><?php

        return ob_get_clean();
    }

    /**
     * Gets the customer reset password WooCommerce email.
     *
     * @return WC_Email_Customer_Reset_Password|null
     */
    protected function getWooCommerceCustomerResetPasswordEmail()
    {
        $wcEmail = $this->getWooCommerceEmail();

        return $wcEmail instanceof WC_Email_Customer_Reset_Password ? $wcEmail : null;
    }
}
