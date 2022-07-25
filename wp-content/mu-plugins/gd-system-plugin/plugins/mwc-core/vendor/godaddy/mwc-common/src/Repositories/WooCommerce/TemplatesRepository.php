<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerceRepository;

/**
 * A repository for handling WooCommerce templates.
 */
class TemplatesRepository
{
    /** @var bool */
    private static $grabbingTemplateOverrides = false;

    /**
     * Determines whether is handling a template overrides request.
     *
     * @return bool
     */
    protected static function isGettingTemplateOverrides() : bool
    {
        return static::$grabbingTemplateOverrides;
    }

    /**
     * Gets a list of WooCommerce template overrides.
     *
     * @return string[] array of found template files overridden by the current theme
     * @throws Exception
     */
    public static function getTemplateOverrides() : array
    {
        $overrides = [];
        $woocommerce = WooCommerceRepository::getInstance();

        if (! $woocommerce || ! isset($woocommerce->api) || ! is_callable([$woocommerce->api, 'get_endpoint_data'])) {
            return $overrides;
        }

        $filter = Register::filter()
            ->setGroup('woocommerce_rest_check_permissions')
            ->setHandler(__CLASS__.'::maybeAllowReadAccessToSystemStatus')
            ->setArgumentsCount(4);

        $filter->execute();

        static::$grabbingTemplateOverrides = true;

        $report = $woocommerce->api->get_endpoint_data('/wc/v3/system_status');

        static::$grabbingTemplateOverrides = false;

        $filter->deregister();

        foreach (ArrayHelper::wrap(ArrayHelper::get($report, 'theme.overrides', [])) as $result) {
            if ($override = ArrayHelper::get(ArrayHelper::wrap($result), 'file')) {
                $overrides[] = $override;
            }
        }

        return $overrides;
    }

    /**
     * Gets a list of WooCommerce email template overrides.
     *
     * @return string[] array of found email template files overridden by the current theme
     * @throws Exception
     */
    public static function getEmailTemplateOverrides() : array
    {
        $emailOverrides = [];

        foreach (static::getTemplateOverrides() as $templateOverride) {
            if (StringHelper::contains($templateOverride, '/emails/')) {
                $emailOverrides[] = $templateOverride;
            }
        }

        return $emailOverrides;
    }

    /**
     * Allows any user to read the WooCommerce System Status report, temporarily.
     *
     * This hook is added while retrieving the list of templates overrides and removed right after.
     * If this event is broadcast from an anonymous request or a request from a user that can't see the Status Report we still want to be able to request report using WooCommerce REST API and get a successful response.
     *
     * @internal
     * @see TemplatesRepository::getTemplateOverrides()
     * @see TemplatesRepository::isGettingTemplateOverrides()
     *
     * @param bool $allowed whether the permission is allowed or not
     * @param string $context context to access the object (read, write)
     * @param string $objectId the ID of the object that the user is requesting access for
     * @param string $objectName the name of the object that the user is requesting access for
     * @return bool
     */
    public static function maybeAllowReadAccessToSystemStatus($allowed, $context, $objectId, $objectName) : bool
    {
        if ('system_status' === $objectName && 'read' === $context && static::isGettingTemplateOverrides()) {
            return true;
        }

        return (bool) $allowed;
    }
}
