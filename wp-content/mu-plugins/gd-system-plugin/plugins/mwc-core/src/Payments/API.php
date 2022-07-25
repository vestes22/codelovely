<?php

namespace GoDaddy\WordPress\MWC\Core\Payments;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\ApplePay\PayController;
use GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\ApplePay\PaymentRequestController;
use GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\CartController;
use GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\GoDaddyPaymentsController;
use GoDaddy\WordPress\MWC\Core\Payments\API\Controllers\ProviderProcessingController;

/**
 * Payments REST API handler.
 */
class API implements ComponentContract
{
    use HasComponentsTrait;

    /** @var string[] */
    protected $componentClasses = [
        GoDaddyPaymentsController::class,
        ProviderProcessingController::class,
        CartController::class,
        PayController::class,
        PaymentRequestController::class,
    ];

    /**
     * Loads the API component.
     *
     * @throws Exception
     */
    public function load()
    {
        Register::action()
            ->setGroup('rest_api_init')
            ->setHandler([$this, 'registerRoutes'])
            ->execute();
    }

    /**
     * Registers the API routes.
     *
     * @internal
     *
     * @throws ComponentLoadFailedException|ComponentClassesNotDefinedException
     */
    public function registerRoutes()
    {
        $this->loadComponents();
    }
}
