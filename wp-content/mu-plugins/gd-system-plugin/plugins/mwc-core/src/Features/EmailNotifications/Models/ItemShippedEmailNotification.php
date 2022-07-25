<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\DataProviderContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\OrderEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\EmailOrderHooksDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\OrderDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\IsOrderEmailNotificationTrait;

/**
 * Model for item shipped email notification.
 */
class ItemShippedEmailNotification extends EmailNotification implements OrderEmailNotificationContract
{
    use IsOrderEmailNotificationTrait;

    /** @var string */
    protected $id = 'customer_item_shipped';

    /** @var string[] */
    protected $categories = ['order'];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setName($this->getId())
            ->setLabel(__('Item shipped', 'mwc-core'))
            ->setDescription(__('Sent to customers when tracking information is added for one or more items in their orders.', 'mwc-core'));
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
                'custom_components' => [
                    'customer_item_shipped_main_content' => $this->getMainContentHtml(),
                ],
            ],
        ];
    }

    /**
     * Gets the HTML for the main content section of the email.
     *
     * @return string
     */
    protected function getMainContentHtml() : string
    {
        ob_start(); ?>
        <p><?php printf(
            /* translators: Placeholder: %s - Customer first name */
            esc_html__('Hi %s,', 'woocommerce'), esc_html($this->getOrder()->getBillingAddress()->getFirstName())); ?></p>
        <p><?php esc_html_e('An item from your order has shipped!', 'woocommerce'); ?></p>
        <?php

        return ob_get_clean();
    }

    /**
     * {@inheritdoc}
     */
    protected function getInitialSettings() : array
    {
        return [
            $this->getEnabledSettingObject(),
            $this->getSubjectSettingObject()
                ->setDefault(__('An item from {{site_title}} order #{{order_number}} has shipped!', 'mwc-core')),
            $this->getPreviewTextSettingObject(),
        ];
    }

    /**
     * Gets the initial email notification data providers.
     *
     * @return DataProviderContract[]
     * @throws Exception
     */
    protected function getInitialDataProviders() : array
    {
        return ArrayHelper::combine(parent::getInitialDataProviders(), [
            new OrderDataProvider($this),
            new EmailOrderHooksDataProvider($this),
        ]);
    }
}
