<?php

use Packlink\BusinessLogic\Controllers\OnboardingController;
use Packlink\Utilities\Response;

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

        Response::json($controller->getCurrentState()->toArray());
    }
}