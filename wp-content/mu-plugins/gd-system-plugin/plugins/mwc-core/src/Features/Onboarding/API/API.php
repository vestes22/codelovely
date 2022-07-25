<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\API;

use Exception;
use GoDaddy\WordPress\MWC\Common\Components\Contracts\ComponentContract;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentClassesNotDefinedException;
use GoDaddy\WordPress\MWC\Common\Components\Exceptions\ComponentLoadFailedException;
use GoDaddy\WordPress\MWC\Common\Components\Traits\HasComponentsTrait;
use GoDaddy\WordPress\MWC\Common\Register\Register;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\API\Controllers\OnboardingSettingsController;

class API implements ComponentContract
{
    use HasComponentsTrait;

    /** @var array controller classes to load/register */
    protected $componentClasses = [
        OnboardingSettingsController::class,
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
     * Registers the onboarding REST API routes.
     *
     * @throws ComponentLoadFailedException
     * @throws ComponentClassesNotDefinedException
     *
     * @see API::load()
     * @internal
     */
    public function registerRoutes()
    {
        $this->loadComponents();
    }
}
