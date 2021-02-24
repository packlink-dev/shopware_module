<?php

use Packlink\BusinessLogic\Controllers\RegistrationRegionsController;
use Packlink\Utilities\Response;

/**
 * Class Shopware_Controllers_Backend_PacklinkRegistrationRegionsController
 */
class Shopware_Controllers_Backend_PacklinkRegistrationRegionsController extends Enlight_Controller_Action
{
    /**
     * Returns regions available for Packlink account registration.
     */
    public function getRegionsAction()
    {
        $controller = new RegistrationRegionsController();

        Response::dtoEntitiesResponse($controller->getRegions());
    }
}