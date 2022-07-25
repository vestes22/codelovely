<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\EmailsRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\WooCommerceEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetWooCommerceEmailOutputTrait;
use WC_Email;
use WP_Hook;

/**
 * This class will load the structured content from a WooCommerce email object.
 */
class ThirdPartyWooCommerceEmailContent extends DefaultEmailContent
{
    use CanGetWooCommerceEmailOutputTrait;

    /** @var WooCommerceEmailNotificationContract */
    protected $emailNotification;

    /** @var array */
    protected $overriddenWordPressHookHandlers = [];

    /**
     * Constructor.
     *
     * @param WooCommerceEmailNotificationContract $emailNotification
     */
    public function __construct(WooCommerceEmailNotificationContract $emailNotification)
    {
        $this->emailNotification = $emailNotification;
    }

    /**
     * Gets the WooCommerce email object.
     *
     * @return WC_Email|null
     */
    public function getWooCommerceEmail()
    {
        return $this->emailNotification->getWooCommerceEmail();
    }

    /**
     * Gets the structured content.
     *
     * Removes any WooCommerce hooks for email template and layout options to return the original email content.
     * Then, restores the hooks after the content is retrieved.
     *
     * @return string
     * @throws Exception
     */
    public function getStructuredContent() : string
    {
        if (! $wooCommerceEmail = $this->getWooCommerceEmail()) {
            return '';
        }

        $this->setConfigurationFromEmailTemplate($this->emailNotification->getTemplate());

        $this->temporarilyOverrideWooCommerceTemplateOptions();
        $this->temporarilyOverrideWooCommerceEmailLayout();

        $content = $this->getStructuredContentFromWooCommerceEmail($wooCommerceEmail);

        $this->restoreWooCommerceEmailLayout();
        $this->restoreWooCommerceTemplateOptions();

        return $content;
    }

    /**
     * Gets structured content from a WooCommerce email object.
     *
     * @param WC_Email $wooCommerceEmail
     * @return string
     */
    protected function getStructuredContentFromWooCommerceEmail(WC_Email $wooCommerceEmail) : string
    {
        $output = $this->tryOutputBufferingCallback(function () use ($wooCommerceEmail) {
            return $wooCommerceEmail->get_content_html();
        });

        if (is_null($output)) {
            return '';
        }

        return '<mj-column><mj-text>'.$this->addInlineStyles($output).'</mj-text></mj-column>';
    }

    /**
     * Temporarily overrides WooCommerce handling for the email layout.
     *
     * @see ThirdPartyWooCommerceEmailContent::getStructuredContent()
     */
    protected function temporarilyOverrideWooCommerceEmailLayout()
    {
        try {
            if ($mailer = EmailsRepository::mailer()) {
                $this->overrideWordPressHookHandler('woocommerce_email_header', [$mailer, 'email_header'], '__return_empty_string');
                $this->overrideWordPressHookHandler('woocommerce_email_footer', [$mailer, 'email_footer'], '__return_empty_string');
            }
        } catch (Exception $exception) {
            // do nothing
        }
    }

    /**
     * Overrides WordPress hooks.
     *
     * @see ThirdPartyWooCommerceEmailContent::getStructuredContent()
     *
     * @param string $hookName
     * @param array|string $currentFunction
     * @param array|string $newFunction
     */
    protected function overrideWordPressHookHandler(string $hookName, $currentFunction, $newFunction)
    {
        if (! $hook = $this->getWordPressHook($hookName)) {
            return;
        }

        foreach ($hook->callbacks as $priority => $handlers) {
            if (! ArrayHelper::accessible($handlers)) {
                continue;
            }

            foreach ($handlers as $identifier => $handler) {
                if ($currentFunction !== ArrayHelper::get($handler, 'function')) {
                    continue;
                }

                $hookPriority = $priority;
                $hookIdentifier = $identifier;
                $hookFunction = ArrayHelper::get($handler, 'function');

                break 2;
            }
        }

        if (! isset($hookPriority, $hookIdentifier, $hookFunction)) {
            return;
        }

        $hook->callbacks[$hookPriority][$hookIdentifier]['function'] = $newFunction;

        $this->overriddenWordPressHookHandlers[$hookName][$hookPriority][$hookIdentifier] = $hookFunction;
    }

    /**
     * Gets a WordPress hook for email content.
     *
     * @see ThirdPartyWooCommerceEmailContent::getStructuredContent()
     *
     * @param string $hookName
     * @return WP_Hook|null
     */
    protected function getWordPressHook(string $hookName)
    {
        // @TODO we shouldn't be accessing globals directly, consider adding a MWC Common repository method instead {unfulvio 2021-12-30}
        // can't use ArrayHelper here because hook names can contain dots
        $hook = $GLOBALS['wp_filter'][$hookName] ?? null;

        if (! $hook instanceof WP_Hook || ! isset($hook->callbacks) || ! ArrayHelper::accessible($hook->callbacks)) {
            return null;
        }

        return $hook;
    }

    /**
     * Restores the WooCommerce email layout.
     *
     * @see ThirdPartyWooCommerceEmailContent::getStructuredContent()
     */
    protected function restoreWooCommerceEmailLayout()
    {
        $this->restoreOverriddenHookHandlers();
    }

    /**
     * Restores the overridden WooCommerce email layout hooks.
     *
     * @see ThirdPartyWooCommerceEmailContent::getStructuredContent()
     */
    protected function restoreOverriddenHookHandlers()
    {
        foreach ($this->overriddenWordPressHookHandlers as $hookName => $callbacks) {
            if (! $hook = $this->getWordPressHook($hookName)) {
                continue;
            }

            foreach ($callbacks as $priority => $handlers) {
                foreach ($handlers as $identifier => $function) {
                    if (isset($hook->callbacks[$priority][$identifier])) {
                        $hook->callbacks[$priority][$identifier]['function'] = $function;
                    }
                }
            }
        }
    }
}
