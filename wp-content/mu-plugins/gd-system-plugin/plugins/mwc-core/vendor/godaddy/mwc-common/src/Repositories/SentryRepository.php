<?php

namespace GoDaddy\WordPress\MWC\Common\Repositories;

use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use Jean85\Exception\VersionMissingExceptionInterface;
use Jean85\PrettyVersions;
use OutOfBoundsException;
use Sentry\Event as SentryEvent;
use function Sentry\init as InitializeSentry;

class SentryRepository
{
    /**
     * Retrieves the current WooCommerce access token.
     */
    public static function initialize()
    {
        if (! ($dsn = Configuration::get('reporting.sentry.dsn')) || ! Configuration::get('reporting.sentry.enabled') || ! static::loadSDK()) {
            return;
        }

        $currentEnv = ManagedWooCommerceRepository::getEnvironment();

        if (! ArrayHelper::contains(['development', 'staging', 'production'], $currentEnv)) {
            return;
        }

        InitializeSentry([
            'dsn'             => $dsn,
            'environment'     => $currentEnv,
            'max_breadcrumbs' => 50, // Amount of trace breadcrumbs -- default is 100
            'release'         => Configuration::get('mwc.version'), // @TODO: Replace version with commit hash {JO 2021-03-03}
            'sample_rate'     => 1, // Percentage of error events to send -- random selection
            'before_send'     => function (SentryEvent $event) {
                if (static::hasSentryException($event)) {
                    return $event;
                }

                return null;
            },
        ]);

        // Set scopes
        static::setSentryScopes();
    }

    /**
     * Checks if can retrieve Sentry version the way Sentry would.
     *
     * This is the way Sentry checks internally and can sometimes fail so we want to bail here if it fails
     * to avoid a 500 error on a customer site.
     *
     * @return bool
     */
    public static function canGetSentryVersion() : bool
    {
        try {
            PrettyVersions::getVersion('sentry/sentry');
        } catch (VersionMissingExceptionInterface $exception) {
            return false;
        } catch (OutOfBoundsException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Checks if exception is explicitly declared as a reportable Sentry Exception.
     *
     * @NOTE If exceptions do not extend the base sentry exception they are not considered reportable.
     *
     * @param SentryEvent $event
     * @return bool
     */
    protected static function hasSentryException(SentryEvent $event) : bool
    {
        foreach (ArrayHelper::wrap($event->getExceptions()) as $exceptionBag) {
            // @NOTE: Only send Exceptions intended for Sentry {JO 2021-03-03}
            if (is_a($exceptionBag->getType(), SentryException::class, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the server instance meets the system requirements for Sentry.
     *
     * @NOTE: The sentry SDK Require PHP 7.2 or higher so we should check before loading any classes.
     *
     * @return bool
     */
    public static function hasSystemRequirements() : bool
    {
        return version_compare(PHP_VERSION, '7.2.0') >= 0;
    }

    /**
     * Set the sentry scopes for tagging.
     */
    protected static function setSentryScopes()
    {
        if (! static::loadSDK()) {
            return;
        }

        \Sentry\configureScope(function (\Sentry\State\Scope $scope) {
            // Use domain as unique identifier
            $scope->setUser(['id' => ArrayHelper::get($_SERVER, 'HTTP_HOST', '')]);
            $scope->setTag('account_plan', Configuration::get('godaddy.account.plan.name') ?? '');
            $scope->setTag('cdn_enabled', Configuration::get('godaddy.cdn.enabled') ? 'yes' : 'no');
            $scope->setTag('request_type', Configuration::get('mwc.mode', ''));
            $scope->setTag('temporary_domain', ManagedWooCommerceRepository::isTemporaryDomain() ? 'yes' : 'no');
            $scope->setTag('managed_wordpress', ManagedWooCommerceRepository::isManagedWordPress() ? 'yes' : 'no');
            $scope->setTag('managed_woocommerce_version', Configuration::get('mwc.version') ?? '');
            $scope->setTag('woocommerce_active', WooCommerceRepository::isWooCommerceActive() ? 'yes' : 'no');
            $scope->setTag('woocommerce_version', Configuration::get('woocommerce.version') ?? '');
            $scope->setTag('wordpress_version', WordPressRepository::getVersion() ?? '');
            $scope->setTag('wordpress_cli_mode', WordPressRepository::isCliMode() ? 'yes' : 'no');
        });
    }

    /**
     * Loads sentry SDK entry point file if system requirements are met.
     *
     * @return bool
     */
    public static function loadSDK() : bool
    {
        // system requirements aren't met
        if (! static::hasSystemRequirements()) {
            return false;
        }

        // TODO: stop manually including the functions.php file when the minimum required PHP version for mwc-core is PHP 7.1 {wvega 2021-07-05}
        $path = static::getSentryFunctionsPath();

        if (! file_exists($path)) {
            return false;
        }

        // already loaded
        if (static::sentryLoaded()) {
            return true;
        }

        require_once $path;

        // @NOTE: If we still can't get version like Sentry package then bail {JO: 2021-08-12}
        if (! static::canGetSentryVersion()) {
            return false;
        }

        return true;
    }

    /**
     * Checks if Sentry SDK loaded or not.
     *
     * @return bool
     */
    public static function sentryLoaded() : bool
    {
        return static::canGetSentryVersion() && function_exists('Sentry\init');
    }

    /**
     * Gets the path to the functions.php included in the sentry package.
     *
     * @return string
     */
    protected static function getSentryFunctionsPath() : string
    {
        // path from vendor/godaddy/mwc-common/src/Repositories to vendor/sentry/sentry/src/functions.php
        return __DIR__.'/../../../../sentry/sentry/src/functions.php';
    }
}
