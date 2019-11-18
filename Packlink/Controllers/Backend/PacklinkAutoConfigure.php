<?php

use Packlink\BusinessLogic\Controllers\AutoConfigurationController;
use Packlink\Utilities\Response;

class Shopware_Controllers_Backend_PacklinkAutoConfigure extends Enlight_Controller_Action
{
    /**
     * Handles auto-configure action.
     */
    public function indexAction()
    {
        $controller = new AutoConfigurationController();

        Response::json(['success' => $controller->start(true)]);
    }
}