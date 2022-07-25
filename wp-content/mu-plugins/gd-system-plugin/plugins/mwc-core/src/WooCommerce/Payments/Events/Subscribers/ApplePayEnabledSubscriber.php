<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\Subscribers;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Common\Events\Contracts\SubscriberContract;
use GoDaddy\WordPress\MWC\Common\Exceptions\SentryException;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Repositories\WordPressRepository;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\ApplePayGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Providers\PoyntProvider;
use GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Events\PaymentGatewayEnabledEvent;
use GoDaddy\WordPress\MWC\Payments\Payments;
use WP_Filesystem_Base;

/**
 * Subscriber for Apple Pay registration event.
 *
 * Registers the merchant with Apple Pay using domain association.
 */
class ApplePayEnabledSubscriber implements SubscriberContract
{
    /** @var string the option key for the Apple Pay registered domain */
    protected $domainOptionKey = 'mwc_payments_apple_pay_domain';

    /**
     * Handles the event.
     *
     * @param PaymentGatewayEnabledEvent|EventContract $event
     * @throws Exception
     */
    public function handle(EventContract $event)
    {
        if (! $this->shouldHandle($event)) {
            return;
        }

        $this->maybeRegisterApplePay();
    }

    /**
     * Determines if the event should be handled.
     *
     * @param EventContract $event
     *
     * @return bool
     */
    protected function shouldHandle(EventContract $event) : bool
    {
        if (! Configuration::get('features.apple_pay')) {
            return false;
        }

        return $event instanceof PaymentGatewayEnabledEvent && 'godaddy-payments-apple-pay' === ArrayHelper::get($event->getData(), 'paymentGateway.id');
    }

    /**
     * Registers for Apple Pay if not already.
     *
     * @throws Exception
     */
    protected function maybeRegisterApplePay()
    {
        $domain = SiteRepository::getDomain();

        // bail if Apple Pay has already been registered for the current domain
        if ($domain === get_option($this->domainOptionKey)) {
            return;
        }

        $applePay = $this->getProviderApplePay();

        try {
            $fileContents = $applePay->getDomainAssociationFile();

            if ($this->storeDomainAssociationFile($fileContents)) {
                $applePay->register();
                update_option($this->domainOptionKey, $domain);
            }
        } catch (Exception $exception) {
            throw new SentryException(sprintf('Could not register site with Apple Pay: %s', $exception->getMessage()));
        }
    }

    /**
     * Writes a file with the Apple Pay domain association.
     *
     * @param string $fileContents
     * @return bool
     * @throws Exception
     */
    protected function storeDomainAssociationFile(string $fileContents) : bool
    {
        if (! $fileContents) {
            throw new Exception('Apple Pay domain association file is empty.');
        }

        WordPressRepository::requireWordPressFilesystem();

        /* @var WP_Filesystem_Base $fileSystem */
        $fileSystem = WordPressRepository::getFilesystem();
        $fileDir = StringHelper::trailingSlash($fileSystem->abspath()).'.well-known';

        if (! $fileSystem->exists($fileDir)) {
            $fileSystem->mkdir($fileDir);
        }

        if (! $fileSystem->is_writable($fileDir)) {
            throw new Exception('Apple Pay domain association file is not writable.');
        }

        $fileName = 'apple-developer-merchantid-domain-association';
        $filePath = StringHelper::trailingSlash($fileDir).$fileName;
        $success = $fileSystem->put_contents($filePath, $fileContents, 0755);

        if (! $success) {
            throw new Exception('Apple Pay domain association file could not be written.');
        }

        return $success;
    }

    /**
     * Gets an instance of the provider's Apple Pay gateway.
     *
     * @return ApplePayGateway
     * @throws Exception
     */
    protected function getProviderApplePay() : ApplePayGateway
    {
        /* @var Payments $payments */
        $payments = Payments::getInstance();
        /* @var PoyntProvider */
        $provider = $payments->provider('poynt');

        return $provider->applePay();
    }
}
