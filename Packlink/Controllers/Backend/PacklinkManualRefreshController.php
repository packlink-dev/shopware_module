<?php

use Packlink\BusinessLogic\Controllers\ManualRefreshController;

class Shopware_Controllers_Backend_PacklinkManualRefreshController extends Enlight_Controller_Action
{
    public function getWhitelistedCSRFActions()
    {
        return ['refresh', 'getTaskStatus'];
    }


    public function refreshAction()
    {
        $controller = new ManualRefreshController();

        $this->View()->assign(['response' => $controller->enqueueUpdateTask()]);
    }

    public function getTaskStatusAction()
    {
        $controller = new ManualRefreshController();

        $this->View()->assign(['response' => $controller->getTaskStatus()]);
    }
}