<?php

namespace GoDaddy\WordPress\MWC\Core\Client;

use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Dashboard as OnboardingDashboard;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Onboarding;

class Features
{
    /**
     * A key-value array of feature flags.
     *
     * @return array [string => bool, ...]
     */
    public function featureFlags() : array
    {
        return [
            'isOnboardingEnabled' => Onboarding::shouldLoad(),
            'isOnboardingDashboardEnabled' => OnboardingDashboard::shouldLoad(),
        ];
    }
}
