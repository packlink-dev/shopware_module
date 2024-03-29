<?php

use Packlink\BusinessLogic\Controllers\DashboardController;

class Shopware_Controllers_Backend_PacklinkDashboard extends Enlight_Controller_Action
{
    /**
     * Retrieves setup status.
     */
    public function indexAction()
    {
        $controller = new DashboardController();

        $status = $controller->getStatus();
        $this->View()->assign($status->toArray());
    }
}
