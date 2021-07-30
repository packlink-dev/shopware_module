<?php

use Packlink\BusinessLogic\Controllers\ModuleStateController;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Backend_PacklinkModuleStateController
 */
class Shopware_Controllers_Backend_PacklinkModuleStateController extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    /**
     * @inheritDoc
     */
    public function getWhitelistedCSRFActions()
    {
        return ['getCurrentState'];
    }

    /**
     * Returns the current state of the module.
     */
    public function getCurrentStateAction()
    {
        $controller = new ModuleStateController();

        $this->View()->assign(['response' => $controller->getCurrentState()->toArray()]);
    }
}