<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Register\Types\RegisterFilter;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Abstract site event class.
 *
 * @since 2.11.0
 */
abstract class AbstractSiteEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var bool whether we are currently trying to retrieve template overrides */
    private $grabbingTemplateOverrides = false;

    /**
     * Constructor.
     *
     * @since 2.11.0
     */
    public function __construct()
    {
        $this->resource = 'site';
    }

    /**
     * Gets the data for the current event.
     *
     * @since 2.11.0
     *
     * @return array
     */
    public function getData() : array
    {
        return [
            'site' => [
                'email_template_overrides' => $this->getEmailTemplateOverrides(),
            ],
        ];
    }

    /**
     * Gets the list of WooCommerce email template overrides.
     *
     * @since 2.11.0
     *
     * @return array
     */
    protected function getEmailTemplateOverrides() : array
    {
        return array_values(array_filter(array_map(function ($override) {
            if (! StringHelper::contains(ArrayHelper::get($override, 'file'), '/emails/')) {
                return null;
            }

            return ArrayHelper::get($override, 'file');
        }, $this->getTemplateOverrides())));
    }

    /**
     * Gets the list of WooCommerce template overrides.
     *
     * @since 2.11.0
     *
     * @return array
     */
    protected function getTemplateOverrides() : array
    {
        if (! $woocommerce = WC()) {
            return [];
        }

        // used is_callable() instead of method_exists() because the latter is unable to see mocked methods
        if (! isset($woocommerce->api) || ! is_callable([$woocommerce->api, 'get_endpoint_data'])) {
            return [];
        }

        /** @var RegisterFilter */
        $filter = Register::filter()
            ->setGroup('woocommerce_rest_check_permissions')
            ->setHandler([$this, 'maybeAllowReadAccessToSystemStatus'])
            ->setArgumentsCount(4);

        $filter->execute();

        $this->grabbingTemplateOverrides = true;

        $report = WC()->api->get_endpoint_data('/wc/v3/system_status');

        $this->grabbingTemplateOverrides = false;

        $filter->deregister();

        return ArrayHelper::get($report, 'theme.overrides', []);
    }

    /**
     * Allows any user to read the System Status report.
     *
     * @internal
     *
     * @since 2.11.0
     *
     * This hook is added while retrieving the list of templates overrides and removed right after.
     *
     * If this event is broadcast from an anonymous request or a request from a user that can't see
     * the Status Report we still want to be able to request report using WooCommerce REST API and
     * get a successful response.
     *
     * @param bool $allowed whether the permission is allowed or not
     * @param string $context context to access the object (read, write)
     * @param string $object_id the ID of the object that the user is requesting access for
     * @param string $object the name of the object that the user is requesting access for
     */
    public function maybeAllowReadAccessToSystemStatus($allowed, $context, $object_id, $object) : bool
    {
        if ($this->grabbingTemplateOverrides && $object === 'system_status' && $context === 'read') {
            return true;
        }

        return (bool) $allowed;
    }
}
