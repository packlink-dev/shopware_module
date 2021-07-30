<?php

use Packlink\BusinessLogic\Controllers\AutoConfigurationController;

class Shopware_Controllers_Backend_PacklinkAutoConfigure extends Enlight_Controller_Action
{
    /**
     * Handles auto-configure action.
     */
    public function indexAction()
    {
        $controller = new AutoConfigurationController();

        $this->View()->assign('response', ['success' => $controller->start(true)]);
    }
}