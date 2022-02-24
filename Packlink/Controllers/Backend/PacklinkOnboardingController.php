<?php

use Packlink\BusinessLogic\Controllers\OnboardingController;

/**
 * Class Shopware_Controllers_Backend_PacklinkOnboardingController
 */
class Shopware_Controllers_Backend_PacklinkOnboardingController extends Enlight_Controller_Action
{
    /**
     * Returns the current state of the on-boarding page.
     */
    public function getCurrentStateAction()
    {
        $controller = new OnboardingController();

        $this->View()->assign(['response' => $controller->getCurrentState()->toArray()]);
    }
}