<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\WooCommerceEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsWooCommerceEmailNotificationTrait;
use WC_Email_Customer_New_Account;

/**
 * Model for new account email notifications.
 */
class NewAccountEmailNotification extends EmailNotification implements WooCommerceEmailNotificationContract
{
    use IsWooCommerceEmailNotificationTrait;

    /** @var string */
    protected $id = 'customer_new_account';

    /** @var string[] */
    protected $categories = ['customer'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setName($this->getId())
            ->setLabel(__('New account', 'mwc-core'))
            ->setDescription(__('Sent to customers when they sign up via checkout or account pages.', 'mwc-core'));
    }

    /**
     * {@inheritdoc}
     */
    protected function getInitialSettings() : array
    {
        return [
            $this->getEnabledSettingObject(),
            $this->getSubjectSettingObject()
                ->setDefault(__('Your {{site_title}} account has been created!', 'mwc-core')),
            $this->getPreviewTextSettingObject(),
        ];
    }

    /**
     * Gets data for the custom components that represent non-editable parts of the email notification.
     *
     * {@inheritdoc}
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
        if (! $wcEmail = $this->getWooCommerceCustomerNewAccountEmail()) {
            return [];
        }

        // set example data for preview
        $wcEmail->user_login = 'example_user_name';
        $wcEmail->password_generated = true;
        $wcEmail->user_pass = 'example_password';

        return $this->getAdditionalData();
    }

    /**
     * Gets the HTML for the main content section of the email.
     *
     * @return string
     */
    protected function getMainContentHtml() : string
    {
        if (! $wcEmail = $this->getWooCommerceCustomerNewAccountEmail()) {
            return '';
        }

        ob_start(); ?>

        <p><?php printf(
                /* translators: %s: Customer username */
                esc_html__('Hi %s,', 'woocommerce'),
                esc_html(stripslashes($wcEmail->user_login))
            ); ?></p>
        <p><?php printf(
                /* translators: %1$s: Site title, %2$s: Username, %3$s: My account link */
                esc_html__('Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view orders, change your password, and more at: %3$s', 'woocommerce'),
                esc_html($wcEmail->get_blogname()),
                '<strong>'.esc_html(stripslashes($wcEmail->user_login)).'</strong>',
                make_clickable(esc_url(wc_get_page_permalink('myaccount')))
            ); ?></p>
        <?php if ($wcEmail->password_generated && $this->isPasswordGeneratingEnabled()) : ?>
        <p><?php printf(
                /* translators: %s: Auto generated password */
                esc_html__('Your password has been automatically generated: %s', 'woocommerce'),
                '<strong>'.esc_html($wcEmail->user_pass).'</strong>'
            ); ?></p>
    <?php endif; ?>
        <?php

        return ob_get_clean();
    }

    /**
     * Determines if automatically generate an account password is enabled.
     *
     * @return bool
     */
    protected function isPasswordGeneratingEnabled() : bool
    {
        return 'yes' === get_option('woocommerce_registration_generate_password');
    }

    /**
     * Gets the customer new account WooCommerce email.
     *
     * @return WC_Email_Customer_New_Account|null
     */
    protected function getWooCommerceCustomerNewAccountEmail()
    {
        $wcEmail = $this->getWooCommerceEmail();

        return $wcEmail instanceof WC_Email_Customer_New_Account ? $wcEmail : null;
    }
}
