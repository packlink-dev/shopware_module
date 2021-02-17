<?php

use Packlink\BusinessLogic\Controllers\ModuleStateController;
use Packlink\Utilities\Response;


/**
 * Class Shopware_Controllers_Backend_PacklinkModuleStateController
 */
class Shopware_Controllers_Backend_PacklinkModuleStateController extends Enlight_Controller_Action
{
    /**
     * Returns the current state of the module.
     */
    public function getCurrentStateAction()
    {
        $controller = new ModuleStateController();

        Response::json($controller->getCurrentState()->toArray());
    }
}