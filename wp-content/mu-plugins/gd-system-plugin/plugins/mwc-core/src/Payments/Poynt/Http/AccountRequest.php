<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http;

use Exception;
use GoDaddy\WordPress\MWC\Common\Configuration\Configuration;
use GoDaddy\WordPress\MWC\Common\Repositories\ManagedWooCommerceRepository;

/**
 * Class AccountRequest.
 */
class AccountRequest extends Request
{
    /** @var string */
    protected $route = 'services/processing-accounts';

    /**
     * Gets the accounts API root.
     *
     * @return string
     * @throws Exception
     */
    public function getRootUrl() : string
    {
        return (string) ManagedWooCommerceRepository::isProductionEnvironment() ? Configuration::get('payments.poynt.accountsApi.productionRoot', '') : Configuration::get('payments.poynt.accountsApi.stagingRoot', '');
    }
}
