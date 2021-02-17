<?php

use Packlink\BusinessLogic\Controllers\ConfigurationController;
use Packlink\Utilities\Response;


/**
 * Class Shopware_Controllers_Backend_PacklinkConfigurationController
 */
class Shopware_Controllers_Backend_PacklinkConfigurationController extends Enlight_Controller_Action
{
    /**
     * Retrieves help link.
     */
    public function getHelpLinkAction()
    {
        $controller = new ConfigurationController();

        Response::json(['helpUrl' => $controller->getHelpLink()]);
    }
}